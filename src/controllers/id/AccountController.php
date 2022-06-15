<?php

namespace craftnet\controllers\id;

use Craft;
use craft\base\Element;
use craft\commerce\behaviors\CustomerBehavior;
use craft\commerce\Plugin as Commerce;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\UploadFailedException;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\web\Controller;
use craft\web\UploadedFile;
use craftnet\behaviors\UserBehavior;
use craftnet\helpers\Address as AddressHelper;
use Throwable;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Class AccountController
 */
class AccountController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @return Response
     */
    public function actionGetAccount(): Response
    {
        /** @var User|UserBehavior $user */
        $user = Craft::$app->getUser()->getIdentity();
        $photoUrl = $user->getPhoto()?->getUrl([
            'mode' => 'crop',
            'width' => 200,
            'height' => 200,
        ], true);

        return $this->asJson([
            'billingAddress' => $this->getBillingAddress($user),
            'card' => $this->getCard($user),
            'cardToken' => $this->getCardToken($user),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'developerName' => $user->developerName,
                'developerUrl' => $user->developerUrl,
                'location' => $user->location,
                'enablePluginDeveloperFeatures' => $user->isInGroup('developers'),
                'enableShowcaseFeatures' => (bool) $user->enableShowcaseFeatures,
                'enablePartnerFeatures' => (bool) $user->enablePartnerFeatures,
                'groups' => $user->getGroups(),
                'photoId' => $user->getPhoto() ? $user->getPhoto()->getId() : null,
                'photoUrl' => $photoUrl,
                'hasApiToken' => $user->apiToken !== null,
                'payPalEmail' => $user->payPalEmail,
            ],
        ]);
    }

    /**
     * Upload a user photo.
     *
     * @return null|Response
     * @throws BadRequestHttpException
     */
    public function actionUploadUserPhoto(): ?Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        if (($file = UploadedFile::getInstanceByName('photo')) === null) {
            return null;
        }

        try {
            if ($file->getHasError()) {
                throw new UploadFailedException($file->error);
            }

            $user = Craft::$app->getUser()->getIdentity();

            // Move to our own temp location
            $fileLocation = Assets::tempFilePath($file->getExtension());
            move_uploaded_file($file->tempName, $fileLocation);
            Craft::$app->getUsers()->saveUserPhoto($fileLocation, $user, $file->name);

            $photoUrl = $user->getPhoto()?->getUrl([
                'mode' => 'crop',
                'width' => 200,
                'height' => 200,
            ], true);

            return $this->asJson([
                'photoId' => $user->photoId,
                'photoUrl' => $photoUrl,
            ]);
        } catch (\Throwable $exception) {
            /** @noinspection UnSafeIsSetOverArrayInspection - FP */
            if (isset($fileLocation) && file_exists($fileLocation)) {
                FileHelper::unlink($fileLocation);
            }

            Craft::error('There was an error uploading the photo: ' . $exception->getMessage(), __METHOD__);

            return $this->asErrorJson(Craft::t('app', 'There was an error uploading your photo: {error}', [
                'error' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * Delete all the photos for current user.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    public function actionDeleteUserPhoto(): Response
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $user = Craft::$app->getUser()->getIdentity();

        if ($user->photoId) {
            Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);
        }

        $user->photoId = null;

        Craft::$app->getElements()->saveElement($user, false);

        return $this->asJson([
            'photoId' => $user->photoId,
            'photoUrl' => $user->getThumbUrl(200),
        ]);
    }

    /**
     * Generate API token.
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGenerateApiToken(): Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        /** @var User|UserBehavior $user */
        $user = Craft::$app->getUser()->getIdentity();

        if (!$user->isInGroup('developers')) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        try {
            $apiToken = $user->generateApiToken();

            return $this->asJson(['apiToken' => $apiToken]);
        } catch (Throwable $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    /**
     * Get the flash messages
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionGetFlashMessages(): Response
    {
        $this->requireAcceptsJson();

        $session = Craft::$app->getSession();

        return $this->asJson([
            'notice' => $session->getFlash('notice', null, true),
            'error' => $session->getFlash('error', null, true)
        ]);
    }

    /**
     * Save billing info.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionSaveBillingInfo(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();
        /** @var User|CustomerBehavior $customer */
        $customer = Craft::$app->getUser()->getIdentity(false);

        $address = new Address();
        $address->title = 'Billing Address';
        $address->id = $this->request->getBodyParam('id');
        $address->firstName = $this->request->getBodyParam('firstName');
        $address->lastName = $this->request->getBodyParam('lastName');
        $address->organization = $this->request->getBodyParam('businessName');
        $address->organizationTaxId = $this->request->getBodyParam('businessTaxId');
        $address->addressLine1 = $this->request->getBodyParam('address1');
        $address->addressLine2 = $this->request->getBodyParam('address2');
        $address->locality = $this->request->getBodyParam('city');
        $address->postalCode = $this->request->getBodyParam('zipCode');
        $address->countryCode = $this->request->getBodyParam('country') ?? 'US';
        $address->administrativeArea = $this->request->getBodyParam('state');
        $address->ownerId = $customer->id;

        $address->setScenario(Element::SCENARIO_LIVE);

        try {
            if (!Craft::$app->getElements()->saveElement($address)) {
                $errors = implode(', ', $address->getErrorSummary(false));
                throw new UserException($errors ?: 'An error occurred saving the billing address.');
            }

            // set this as the user's primary billing address
            $customer->primaryBillingAddressId = $address->id;
            if (!Craft::$app->getElements()->saveElement($customer)) {
                $errors = implode(', ', $customer->getErrorSummary(false));
                throw new UserException($errors ?: 'An error occurred saving the billing address.');
            }

            $addressArray = AddressHelper::toV1Array($address);

            return $this->asJson([
                'success' => true,
                'address' => $addressArray,
            ]);
        } catch (Throwable $e) {
            return $this->asErrorJson($e->getMessage());
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param User $user
     *
     * @return array|null
     */
    private function getCard(User $user): ?array
    {
        $paymentSources = Commerce::getInstance()->getPaymentSources()->getAllPaymentSourcesByUserId($user->id);

        if (\count($paymentSources) === 0) {
            return null;
        }

        $paymentSource = $paymentSources[0];
        $response = Json::decode($paymentSource->response);

        if (isset($response['object'])) {
            switch ($response['object']) {
                case 'card':
                    return $response;

                case 'source':
                    return $response['card'];

                case 'payment_method':
                    return $response['card'];
            }
        }

        return null;
    }

    /**
     * @param User $user
     * @return null|string
     */
    private function getCardToken(User $user): ?string
    {
        $paymentSources = Commerce::getInstance()->getPaymentSources()->getAllPaymentSourcesByUserId($user->id);

        if (\count($paymentSources) === 0) {
            return null;
        }

        $paymentSource = $paymentSources[0];

        return $paymentSource->token;
    }

    /**
     * @param User $user
     * @return array|null
     */
    private function getBillingAddress(User $user): ?array
    {
        /** @var User|CustomerBehavior $user */
        $primaryBillingAddress = $user->getPrimaryBillingAddress();

        if (!$primaryBillingAddress) {
            return null;
        }

        return AddressHelper::toV1Array($primaryBillingAddress);
    }
}
