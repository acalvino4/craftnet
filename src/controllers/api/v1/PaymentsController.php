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
use craft\elements\Address;
use craft\helpers\App;
use craft\helpers\StringHelper;
use craftnet\behaviors\OrderBehavior;
use craftnet\controllers\api\RateLimiterTrait;
use craftnet\errors\ValidationException;
use craftnet\paymentmethods\PaymentMethodRecord;
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

            // pay
            /** @var PaymentForm $paymentForm */
            $paymentForm = $gateway->getPaymentFormModel();

            try {
                $this->_populatePaymentForm($cart, $payload, $gateway, $paymentForm);

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
            if (CRAFT_SITE === 'craftId') {
                $response = ['redirect' => $redirect];
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
    private function _populatePaymentForm(Order $cart, \stdClass $payload, StripeGateway $gateway, PaymentForm $paymentForm)
    {

        if ($cart->getPaymentSource()) {
            $paymentForm->populateFromPaymentSource($cart->getPaymentSource());
        } else {
            $paymentForm->paymentMethodId = $payload->token;
        }

        $commerce = Commerce::getInstance();
        $stripe = Stripe::getInstance();
        $paymentSourcesService = $commerce->getPaymentSources();
        $customersService = $stripe->getCustomers();
        $savePaymentMethod = (bool) ($payload?->savePaymentMethod ?? false);
        $makePrimary = $savePaymentMethod && ($payload?->makePrimary ?? false);
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
            'email' => $cart->getEmail(),
        ];

        // Fetch a potentially existing customer and maybe set the billing details on the payment method
        if ($this->_isPaymentMethod($paymentForm)) {
            $stripeCustomerId = StripePaymentMethod::retrieve($paymentForm->paymentMethodId)?->customer;
            if ($this->_includeBillingDetails($cart)) {
                StripePaymentMethod::update($paymentForm->paymentMethodId, ['billing_details' => $customerData]);
            }
        } else {
            $stripeCustomerId = StripeSource::retrieve($paymentForm->paymentMethodId)?->customer;
            if ($this->_includeBillingDetails($cart)) {
                StripeSource::update($paymentForm->paymentMethodId, ['owner' => $customerData]);
            }
        }

        // If we had a customer stored on the payment method, no need to tell it to use the payment method
        if ($stripeCustomerId) {
            $stripeCustomer = StripeCustomer::update($stripeCustomerId, $customerData);

        // If there was no customer stored on payment method
        } else {
            // TODO: wat?
            $customerData['source'] = $payload->token;
            $customerData['description'] = 'Guest customer created for order #' . $payload->orderNumber;

            // If a user is logged in and they wish to store this card
            if ($this->currentUser && $makePrimary) {
                // TODO: should we use makePrimaryBillingAddress/makePrimaryPaymentSource when it exists?

                // Fetch a customer
                $customer = $customersService->getCustomer($gateway->id, $this->currentUser);

                // Update the customer data
                $stripeCustomer = StripeCustomer::update($customer->reference, $customerData);
                $customer->response = $stripeCustomer->jsonSerialize();

                $customersService->saveCustomer($customer);
            } else {
                // Otherwise create an anonymous customer
                $stripeCustomer = StripeCustomer::create($customerData);
            }
        }

        $paymentForm->customer = $stripeCustomer->id;

        // If there's no need to make anything primary - bye!
        if (!$this->currentUser || !$savePaymentMethod) {
            return;
        }

        // Retrieve the freshest of data
        if ($this->_isPaymentMethod($paymentForm)) {
            $stripeResponse = StripePaymentMethod::retrieve($paymentForm->paymentMethodId);
        } else {
            $stripeResponse = StripeSource::retrieve($paymentForm->paymentMethodId);
        }

        // Set it as the customer default for subscriptions
        /** @phpstan-ignore-next-line */
        $stripeCustomer->invoice_settings = [
            'default_payment_method' => $paymentForm->paymentMethodId,
        ];
        $stripeCustomer->save();

        // save it for Commerce
        $paymentSource = new PaymentSource([
            'customerId' => $this->currentUser->id,
            'gatewayId' => $gateway->id,
            'token' => $stripeResponse->id,
            'response' => $stripeResponse->toJSON(),
            'description' => 'Default Source',
        ]);

        if (!$paymentSourcesService->savePaymentSource($paymentSource)) {
            throw new PaymentSourceException('Could not create the payment method: ' . implode(', ', $paymentSource->getErrorSummary(true)));
        }

        $userBillingAddress = $billingAddress ? Craft::$app->getElements()->duplicateElement(
            $billingAddress,
            ['ownerId' => $this->currentUser->id],
        ) : null;

        $cart->sourceBillingAddressId = $billingAddress->id;

        $paymentMethod = new PaymentMethodRecord();
        $paymentMethod->paymentSourceId = $paymentSource->id;
        $paymentMethod->billingAddressId = $userBillingAddress?->id;
        $paymentMethod->ownerId = $this->currentUser->id;

        if (!$paymentMethod->save()) {
            throw new Exception('Unable to save payment method.');
        }

        if ($makePrimary) {
            $this->currentUser->setPrimaryPaymentSourceId($paymentSource->id);
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
     * @param PaymentForm $paymentForm
     * @return bool
     */
    private function _isPaymentMethod(PaymentForm $paymentForm): bool
    {
        return StringHelper::startsWith($paymentForm->paymentMethodId, 'pm_');
    }
}
