<?php

namespace craftnet\controllers\console;

use Craft;
use craft\commerce\Plugin as Commerce;
use craft\helpers\App;
use craftnet\orgs\Org;
use craftnet\paymentmethods\PaymentMethodRecord;
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
        $makePrimary = (bool) $this->request->getBodyParam('makePrimary', false);
        $paymentMethod = $isNew
            ? new PaymentMethodRecord()
            : PaymentMethodRecord::findOne([
                'id' => $paymentMethodId,
                'ownerId' => $this->currentUser->id,
            ]);

        if (!$paymentMethod) {
            throw new NotFoundHttpException();
        }

        $billingAddressId = (int) $this->request->getBodyParam('billingAddressId', $paymentMethod->billingAddressId);

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

        if (!$paymentSource) {
            throw new BadRequestHttpException();
        }

        if ($makePrimary) {
            $this->currentUser->setPrimaryPaymentSourceId($paymentSource->id);
            $this->currentUser->setPrimaryBillingAddressId($billingAddressId);

            if (!Craft::$app->getElements()->saveElement($this->currentUser)) {
                throw new BadRequestHttpException();
            }
        }

        $paymentMethod->paymentSourceId = $paymentSource->id;
        $paymentMethod->billingAddressId = $billingAddressId;
        $paymentMethod->ownerId = $this->currentUser->id;

        return $paymentMethod->save()
            ? $this->asSuccess('Payment method saved.')
            : $this->asFailure();
    }

    public function actionDeletePaymentMethod(int $paymentMethodId): Response
    {
        $paymentMethod = PaymentMethodRecord::findOne(['id' => $paymentMethodId]);

        if (!$paymentMethod) {
            throw new NotFoundHttpException();
        }

        // Payment method will be deleted by cascade
        $deleted = Commerce::getInstance()
            ->getPaymentSources()
            ->deletePaymentSourceById($paymentMethod->paymentMethodId);

        return $deleted ? $this->asSuccess('Payment method deleted.') : $this->asFailure();
    }

    public function actionGetPaymentMethods(): Response
    {
        $orgs = Org::find()->hasMember($this->currentUser)->collect();
        $paymentMethods = Collection::make($this->currentUser->getPaymentMethods())
            ->sortByDesc(fn(PaymentMethodRecord $paymentMethod) => $paymentMethod->paymentSource->isPrimary ? 1 : 0)
            ->concat($orgs)
            ->map(function(PaymentMethodRecord|Org $orgOrPaymentMethod) {
                $org = $orgOrPaymentMethod instanceof Org ? $orgOrPaymentMethod : null;

                /** @var PaymentMethodRecord|null $paymentMethod */
                $paymentMethod = $org ? $org->getPaymentMethod() : $orgOrPaymentMethod;
                $paymentSource = $paymentMethod?->getPaymentSource();
                $billingAddress = $paymentMethod?->getBillingAddress();

                if (!$paymentMethod) {
                    return null;
                }

                return $paymentMethod->getAttributes([
                    'id',
                ]) + [
                    'card' => $paymentSource?->getCard(),
                    'org' => $org ? static::transformOrg($org) + [
                        'canPurchase' => $org->canPurchase($this->currentUser),
                    ] : null,
                    'isPrimary' => (bool) $paymentSource?->isPrimary,
                    'paymentSourceId' => $paymentSource?->id,
                    'billingAddressId' => $billingAddress?->id,
                ];
            })
            ->whereNotNull()
            ->values();

        return $this->asSuccess(data: ['paymentMethods' => $paymentMethods]);
    }
}
