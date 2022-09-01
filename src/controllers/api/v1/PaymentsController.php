<?php

namespace craftnet\controllers\api\v1;

use Craft;
use craft\commerce\elements\Order;
use craft\commerce\errors\PaymentException;
use craft\commerce\errors\PaymentSourceException;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\commerce\Plugin as Commerce;
use craft\commerce\stripe\gateways\PaymentIntents as StripeGateway;
use craft\commerce\stripe\models\forms\payment\PaymentIntent as PaymentForm;
use craft\commerce\stripe\Plugin as Stripe;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craftnet\behaviors\OrderBehavior;
use craftnet\controllers\api\RateLimiterTrait;
use craftnet\errors\ValidationException;
use craftnet\records\PaymentMethod;
use Stripe\Customer as StripeCustomer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Source as StripeSource;
use yii\base\Exception;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class PaymentsController
 */
class PaymentsController extends CartsController
{
    use RateLimiterTrait;

    protected int|bool|array $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    // Properties
    // =========================================================================

    public $defaultAction = 'pay';

    // Public Methods
    // =========================================================================

    /**
     * Processes a payment for an order.
     *
     * @return Response
     * @throws Exception
     * @throws ValidationException if the order number isn't valid or isn't ready to be purchased
     * @throws BadRequestHttpException if there was an issue with the payment
     */
    public function actionPay(): Response
    {
        $payload = $this->getPayload('payment-request');

        try {

            /** @var Order|OrderBehavior $cart */
            $cart = $this->getCart($payload->orderNumber);
        } catch (UserException $e) {
            throw new ValidationException([
                [
                    'param' => 'orderNumber',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode() === 404 ? self::ERROR_CODE_MISSING : self::ERROR_CODE_INVALID,
                ],
            ], null, 0, $e);
        }

        $errors = [];
        $commerce = Commerce::getInstance();

        // make sure the cart has an email
        if (!$cart->getEmail()) {
            throw new ValidationException([
                [
                    'param' => 'email',
                    'message' => 'The cart is missing an email',
                    'code' => self::ERROR_CODE_INVALID,
                ],
            ]);
        }

        // make sure the cart has a billing address
        if ($cart->getBillingAddress() === null) {
            $errors[] = [
                'param' => 'orderNumber',
                'message' => 'The cart is missing a billing address',
                'code' => self::ERROR_CODE_INVALID,
            ];
        }

        // make sure the cart isn't empty
        if ($cart->getIsEmpty()) {
            $errors[] = [
                'param' => 'orderNumber',
                'message' => 'The cart is empty',
                'code' => self::ERROR_CODE_INVALID,
            ];
        }

        // make sure the cost is in line with what they were expecting
        $totalPrice = $cart->getTotalPrice();
        if (round($payload->expectedPrice) < round($totalPrice)) {
            $formatter = Craft::$app->getFormatter();
            $fmtExpected = $formatter->asCurrency($payload->expectedPrice, 'USD', [], [], true);
            $fmtTotal = $formatter->asCurrency($totalPrice, 'USD', [], [], true);
            $errors[] = [
                'param' => 'expectedPrice',
                'message' => "Expected price ({$fmtExpected}) was less than the order total ({$fmtTotal}).",
                'code' => self::ERROR_CODE_INVALID,
            ];
        }

        // if there are any errors, send them now before the point of no return
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $redirect = null;

        // only process a payment if there's a price
        if ($totalPrice) {

            // get the gateway
            /** @var StripeGateway $gateway */
            $gateway = $commerce->getGateways()->getGatewayById(App::env('STRIPE_GATEWAY_ID'));

            /** @var PaymentForm $paymentForm */
            $paymentForm = $gateway->getPaymentFormModel();

            try {

                // Gets the payment form populated as well as ensuring the payment source is saved if requested.
                $this->_preparePaymentInfo($cart, $payload, $gateway, $paymentForm);

                // Attempt payment
                $commerce->getPayments()->processPayment($cart, $paymentForm, $redirect, $transaction);
            } catch (ApiErrorException $e) {
                throw new BadRequestHttpException($e->getMessage(), 0, $e);
            } catch (PaymentException $e) {
                throw new BadRequestHttpException($e->getMessage(), 0, $e);
            }
        } else {
            // just mark it as complete since it's a free order
            $cart->markAsComplete();
        }

        if (empty($redirect)) {
            $response = ['completed' => true];
        } else {
            if (CRAFT_SITE === 'console') {
                // TODO @tim if this is a redirect for SCA, we need to not redirect the current user but start the approval process
                if ($this->currentUser->id !== $cart->getCustomer()->id) {
                    $response = ['approvalNeeded' => true];
                } else {
                    $response = ['redirect' => $redirect];
                }
            } else {
                throw new BadRequestHttpException('Cards that require strong customer authentication cannot be processed from the in-app Plugin Store. Please update to Craft CMS 3.7.37 or later, and try again.');
            }
        }
        /** @var Transaction $transaction */
        if (isset($transaction)) {
            $response['transaction'] = $transaction->toArray();
        }
        return $this->asJson($response);
    }

    /**
     * Populates a Stripe payment form from the payload.
     *
     * @param Order $cart
     * @param \stdClass $payload
     * @param StripeGateway $gateway
     * @param PaymentForm $paymentForm
     * @throws PaymentSourceException
     */
    private function _preparePaymentInfo(Order $cart, \stdClass $payload, StripeGateway $gateway, PaymentForm $paymentForm)
    {
        // Prepare general
        $commerce = Commerce::getInstance();
        $stripe = Stripe::getInstance();
        $commercePaymentSourcesService = $commerce->getPaymentSources();
        $stripeCustomersService = $stripe->getCustomers();

        // Prepare customer
        $cartCustomer = $cart->getCustomer();
        // Ensure we always have the correct stripe customer (Will create one if non exists for user)
        // The stripe customer will always be the cart customer, even if the current user is not the cart customer.
        $stripePluginCustomer = $stripeCustomersService->getCustomer($gateway->id, $cartCustomer);

        // Prepare address and customer information
        $billingAddress = $cart->getBillingAddress();
        $customerData = [
            'address' => [
                'line1' => $billingAddress?->addressLine1,
                'line2' => $billingAddress?->addressLine2,
                'country' => $billingAddress?->getCountryCode(),
                'city' => $billingAddress?->getLocality(),
                'postal_code' => $billingAddress?->getPostalCode(),
                'state' => $billingAddress?->getAdministrativeArea(),
            ],
            'name' => $billingAddress?->fullName,
            'email' => $cart->getEmail(), // always the same as the cart customer email
        ];

        // Using an existing payment source or a new one?
        $newCard = false;
        if ($cartPaymentSource = $cart->getPaymentSource()) {
            $paymentToken = $cartPaymentSource->token;
        } else {
            $newCard = true;
            $paymentToken = $payload->token;
        }

        // Prepare saving payment method options selected
        $savePaymentMethod = (bool)($payload?->savePaymentMethod ?? false);
        $makePrimaryPaymentMethod = $savePaymentMethod && ($payload?->makePrimary ?? false);

        // Update the payment source in stripe with the latest customer billing details
        $stripePaymentSourceOrMethod = null;
        if ($this->_isPaymentMethod($paymentToken)) {
            $stripePaymentSourceOrMethod = StripePaymentMethod::retrieve($paymentToken);
            if ($this->_includeBillingDetails($cart)) {
                StripePaymentMethod::update($paymentToken, ['billing_details' => $customerData]);
            }
        } else {
            $stripePaymentSourceOrMethod = StripeSource::retrieve($paymentToken);
            if ($this->_includeBillingDetails($cart)) {
                StripeSource::update($paymentToken, ['owner' => $customerData]);
            }
        }

        // Do the main job of this process
        $paymentForm->paymentMethodId = $paymentToken;
        $paymentForm->customer = $stripePluginCustomer->reference;

        // Update the stripe customer up to date
        $stripeCustomer = StripeCustomer::update($stripePluginCustomer->reference, $customerData);
        $stripePluginCustomer->response = $stripeCustomer->jsonSerialize(); // Keep the plugin customer data up to date
        $stripeCustomersService->saveCustomer($stripePluginCustomer);

        // If they don’t have a new card, and they don’t want to save it, then we can just return
        if (!$newCard || !$savePaymentMethod || !$this->_isPaymentMethod($paymentToken)) {
            return;
        }

        // Save the payment method in stripe
        $stripePaymentSourceOrMethod->attach(['customer' => $stripePluginCustomer->reference]);

        // Save the Commerce payment source
        $paymentSource = new PaymentSource([
            'customerId' => $cart->getCustomer()->id,
            'gatewayId' => $gateway->id,
            'token' => $stripePaymentSourceOrMethod->id,
            'response' => $stripePaymentSourceOrMethod->toJSON(),
            'description' => 'Credit Card',
        ]);

        if (!$commercePaymentSourcesService->savePaymentSource($paymentSource)) {
            throw new PaymentSourceException('Could not create the payment method: ' . implode(', ', $paymentSource->getErrorSummary(true)));
        }

        // Save the address to the user address book
        $userBillingAddress = $billingAddress ? Craft::$app->getElements()->duplicateElement(
            $billingAddress,
            ['ownerId' => $this->currentUser->id],
        ) : null;
        $cart->sourceBillingAddressId = $billingAddress->id;

        // Save the craftnet payment method
        $paymentMethod = new PaymentMethod();
        $paymentMethod->paymentSourceId = $paymentSource->id;
        $paymentMethod->billingAddressId = $userBillingAddress?->id;
        $paymentMethod->ownerId = $this->currentUser->id;

        if (!$paymentMethod->save()) {
            throw new Exception('Unable to save payment method.');
        }

        // Making a primary payment method is only possible for new cards for the current logged in user.
        if ($makePrimaryPaymentMethod) {
            $this->currentUser->setPrimaryPaymentSourceId($paymentSource->id);

            // Set it as the customer default in stripe
            /** @phpstan-ignore-next-line */
            $stripeCustomer->invoice_settings = [
                'default_payment_method' => $stripePaymentSourceOrMethod->id,
            ];
            $stripeCustomer->save();

            $this->currentUser->setPrimaryBillingAddressId($billingAddress->id);

            if (!Craft::$app->getElements()->saveElement($this->currentUser)) {
                throw new Exception('Unable to save user.');
            }
        }
    }

    private function _includeBillingDetails(Order $cart): bool
    {
        if ($cart->getTotalPrice() > App::env('SPS_MAX') ?? 0) {
            return false;
        }

        if ($cart->getCustomer()?->active ?? false) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $paymentToken
     * @return bool
     */
    private function _isPaymentMethod(mixed $paymentToken): bool
    {
        return StringHelper::startsWith($paymentToken, 'pm_');
    }
}
