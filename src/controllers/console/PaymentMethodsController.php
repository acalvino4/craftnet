<?php

namespace craftnet\controllers\console;

use Craft;
use craft\base\Element;
use craft\commerce\Plugin;
use craft\commerce\Plugin as Commerce;
use craft\elements\Address;
use craft\helpers\App;
use craftnet\orgs\Org;
use craftnet\records\PaymentMethod;
use Illuminate\Support\Collection;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PaymentMethodsController extends BaseController
{
    public function actionSavePaymentMethod(?int $paymentMethodId = null): Response
    {
        $isNew = !$paymentMethodId;
        $description = $this->request->getBodyParam('description');
        $billingAddressParam = $this->request->getBodyParam('billingAddress', []);
        $makePrimary = (bool) $this->request->getBodyParam('makePrimary', false);
        $paymentMethod = $isNew
            ? new PaymentMethod()
            : PaymentMethod::findOne([
                'id' => $paymentMethodId,
                'ownerId' => $this->currentUser->id,
            ]);

        if (!$paymentMethod) {
            throw new NotFoundHttpException();
        }

        Plugin::getInstance()->getCustomers()->ensureCustomer($this->currentUser);

        if ($isNew) {
            $gateway = Commerce::getInstance()
                ->getGateways()
                ->getGatewayById(App::env('STRIPE_GATEWAY_ID'));

            if (!$gateway || !$gateway->supportsPaymentSources()) {
                throw new BadRequestHttpException();
            }

            $paymentForm = $gateway->getPaymentFormModel();
            $paymentForm->setAttributes($this->request->getBodyParams(), false);
            $paymentSource = Commerce::getInstance()
                ->getPaymentSources()
                ->createPaymentSource($this->currentUser->id, $gateway, $paymentForm, $description);
        } else {
            $paymentSource = Commerce::getInstance()
                ->getPaymentSources()
                ->getPaymentSourceById($paymentMethod->paymentSourceId);

            if ($description && $description !== $paymentSource->description) {
                $paymentSource->description = $description;

                if (!Commerce::getInstance()->getPaymentSources()->savePaymentSource($paymentSource)) {
                    throw new BadRequestHttpException();
                }
            }
        }

        $billingAddress = $paymentMethod->billingAddress ?? Craft::createObject(Address::class);

        if ($billingAddressParam) {
            $billingAddress->title = "Billing address for $paymentSource->description";
            $billingAddress->ownerId = $this->currentUser->id;
            $billingAddress->setScenario(Element::SCENARIO_LIVE);
            $billingAddress->setAttributes($billingAddressParam);

            if (!Craft::$app->getElements()->saveElement($billingAddress)) {

                // Clean up orphaned payment source
                if ($isNew) {
                    Commerce::getInstance()
                        ->getPaymentSources()
                        ->deletePaymentSourceById($paymentSource->id);
                }

                return $this->asModelFailure($billingAddress);
            }
        }

        $paymentMethod->paymentSourceId = $paymentSource->id;
        $paymentMethod->billingAddressId = $billingAddress->id;
        $paymentMethod->ownerId = $this->currentUser->id;

        if (!$paymentMethod->save()) {
            return $this->asModelFailure($paymentMethod);
        }

        if ($makePrimary) {
            $this->currentUser->setPrimaryPaymentSourceId($paymentSource->id);
            $this->currentUser->setPrimaryBillingAddressId($billingAddress->id);

            if (!Craft::$app->getElements()->saveElement($this->currentUser)) {
                return $this->asModelFailure($this->currentUser);
            }
        }

        return $this->asSuccess('Payment method saved.');
    }

    public function actionDeletePaymentMethod(int $paymentMethodId): Response
    {
        $paymentMethod = PaymentMethod::findOne(['id' => $paymentMethodId]);

        if (!$paymentMethod) {
            throw new NotFoundHttpException();
        }

        return $paymentMethod->delete() ? $this->asSuccess('Payment method deleted.') : $this->asFailure();
    }
    public function actionGetPaymentMethods(): Response
    {
        $paymentMethods = Collection::make($this->currentUser->getPaymentMethods())
            ->map(fn(PaymentMethod $paymentMethod) => $this->_transformPaymentMethod($paymentMethod) + [
                'orgs' => $paymentMethod->getOrgs()->hasOwner($this->currentUser)
                    ->collect()
                    ->map(fn(Org $org) => static::transformOrg($org)),
            ]);

        return $this->asSuccess(data: ['paymentMethods' => $paymentMethods]);
    }

    public function actionGetPaymentMethodsForCheckout(): Response
    {
        $orgs = Org::find()->hasMember($this->currentUser)->collect();
        $paymentMethods = Collection::make($this->currentUser->getPaymentMethods())
            ->concat($orgs)
            ->map(function(PaymentMethod|Org $orgOrPaymentMethod) {
                $org = $orgOrPaymentMethod instanceof Org ? $orgOrPaymentMethod : null;

                /** @var PaymentMethod|null $paymentMethod */
                $paymentMethod = $org ? $org->getPaymentMethod() : $orgOrPaymentMethod;

                if (!$paymentMethod) {
                    return null;
                }

                return $this->_transformPaymentMethod($paymentMethod) + [
                    'org' => $org ? static::transformOrg($org) + [
                        'canPurchase' => $org->canPurchase($this->currentUser),
                    ] : null,
                ];
            })
            ->whereNotNull()
            ->values();

        return $this->asSuccess(data: ['paymentMethods' => $paymentMethods]);
    }

    private function _transformPaymentMethod(PaymentMethod $paymentMethod): array
    {
        $paymentSource = $paymentMethod?->getPaymentSource();
        $billingAddress = $paymentMethod?->getBillingAddress();
        $billingAddress?->setScenario(Element::SCENARIO_LIVE);

        return $paymentMethod->getAttributes([
            'id',
            'paymentSourceId',
        ]) + [
            'billingAddress' => $billingAddress ? static::transformAddress($billingAddress) : null,
            'token' => $paymentSource?->token,
            'description' => $paymentSource?->description,
            'card' => $paymentSource?->getCard(),
            'isPrimary' => (bool) $paymentSource?->isPrimary,
        ];
    }
}
