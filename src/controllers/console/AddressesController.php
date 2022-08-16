<?php

namespace craftnet\controllers\console;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\Country\Country;
use CommerceGuys\Addressing\Subdivision\Subdivision;
use Craft;
use craft\elements\Address;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craftnet\behaviors\AddressBehavior;
use craftnet\behaviors\UserBehavior;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class AddressesController
 */
class AddressesController extends BaseController
{
    protected const ADDRESS_INFO_CACHE_DURATION = 60 * 60 * 24 * 7;
    protected const COUNTRIES_CACHE_DURATION = 60 * 60 * 24 * 7;

    public function actionGetCountries(): Response
    {
        $countries = Craft::$app->getCache()->getOrSet(__METHOD__, function() {
            return Collection::make(Craft::$app->getAddresses()->getCountryRepository()->getAll())
                ->map(fn(Country $country) => [
                    'countryCode' => $country->getCountryCode(),
                    'name' => $country->getName(),
                ])
                ->all();
        }, self::COUNTRIES_CACHE_DURATION);

        return $this->asSuccess(data: ['countries' => $countries]);
    }

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionGetAddressInfo(): Response
    {
        $this->requireAcceptsJson();
        $parents = Craft::$app->getRequest()->getRequiredParam('parents');

        if (!is_array($parents) || empty($parents)) {
            throw new BadRequestHttpException();
        }

        $cacheKey = [__METHOD__ => $parents];
        $addressInfo = Craft::$app->getCache()->getOrSet($cacheKey, function() use ($parents) {
            $subdivisionRepo = Craft::$app->getAddresses()->getSubdivisionRepository();
            $addressFormatRepo = Craft::$app->getAddresses()->getAddressFormatRepository();

            // First item must always be a country
            $country = $addressFormatRepo->get(Collection::make($parents)->first());
            $subdivisions = Collection::make($subdivisionRepo->getAll($parents));

            return [
                'format' => $this->_formatAsArray($country),
                'subdivisions' => $subdivisions->map(fn($subdivision) => $this->_subdivisionsAsArray($subdivision)),
            ];
        }, self::ADDRESS_INFO_CACHE_DURATION);

        return $this->asSuccess(data: ['addressInfo' => $addressInfo]);
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws InvalidConfigException
     * @throws Exception
     * @throws NotFoundHttpException
     */
    public function actionSaveAddress(?int $addressId = null): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();
        $isPrimaryBilling = (bool) $this->request->getBodyParam('isPrimaryBilling');
        $isPrimaryShipping = (bool) $this->request->getBodyParam('isPrimaryShipping');

        /** @var Address $address */
        $address = $addressId ?
            Address::find()
                ->id($addressId)
                ->ownerId($user->id)
                ->one() :
            new Address(['ownerId' => $user->id]);

        if (!$address) {
            throw new NotFoundHttpException('Address not found.');
        }


        $address->setAttributes($this->request->getBodyParams());
        $address->isPrimaryBilling = $isPrimaryBilling;
        $address->isPrimaryShipping = $isPrimaryShipping;
        $saved = Craft::$app->getElements()->saveElement($address);

        return $saved ?
            $this->asModelSuccess($address, 'Address saved.') :
            $this->asModelFailure($address, 'Could not save address.');
    }

    /**
     * @throws Throwable
     * @throws NotFoundHttpException
     */
    public function actionRemoveAddress(int $addressId = null): ?Response
    {
        $user = Craft::$app->getUser()->getIdentity();

        /** @var Address|AddressBehavior $address */
        $address = Address::find()
            ->id($addressId)
            ->ownerId($user->id)
            ->one();

        if (!$address) {
            throw new NotFoundHttpException('Address not found.');
        }

        if ($address->getOrgs()->exists()) {
            $this->requireElevatedSession();
        }

        $deleted = Craft::$app->getElements()->deleteElementById($addressId);

        return $deleted ?
            $this->asModelSuccess($address, 'Address removed.') :
            $this->asModelFailure($address, 'Could not remove address.');
    }

    public function actionGetAddresses(): ?Response
    {
        /** @var User|UserBehavior $user */
        $user = Craft::$app->getUser()->getIdentity();

        $addresses = Collection::make($user->getAddresses())
            ->map(function(Address|AddressBehavior $address) {
                $orgs = $address->getOrgs()->collect();

                return $address->getAttributes() + [
                        'orgs' => $orgs->isEmpty() ? null : $address->getOrgs()->collect()
                            ->map(fn($org) => static::transformOrg($org)),
                    ];
            });

        return $this->asSuccess(data: ['addresses' => $addresses->all()]);
    }

    /**
     * @param Subdivision $subdivision
     * @return array
     */
    private function _subdivisionsAsArray(Subdivision $subdivision): array
    {
        return [
            'name' => $subdivision->getName(),
            'code' => $subdivision->getCode(),
            'locale' => $subdivision->getLocale(),
            'countryCode' => $subdivision->getCountryCode(),
            'hasChildren' => (bool)$subdivision->hasChildren(),
            'children' => Collection::make($subdivision->getChildren())
                ->map(fn($subdivision) => $this->_subdivisionsAsArray($subdivision)),
        ];
    }

    /**
     * @param AddressFormat $format
     * @return array
     */
    private function _formatAsArray(AddressFormat $format): array
    {
        return [
            'countryCode' => $format->getCountryCode(),
            'locale' => $format->getLocale(),
            'subdivisionDepth' => $format->getSubdivisionDepth(),
            'administrativeAreaType' => $format->getAdministrativeAreaType(),
            'administrativeAreaTypeLabel' => Craft::$app->getAddresses()->getAdministrativeAreaTypeLabel($format->getAdministrativeAreaType()),
            'localityType' => $format->getLocalityType(),
            'localityTypeLabel' => Craft::$app->getAddresses()->getAdministrativeAreaTypeLabel($format->getLocalityType()),
            'dependentLocalityType' => $format->getDependentLocalityType(),
            'dependentLocalityTypeLabel' => Craft::$app->getAddresses()->getAdministrativeAreaTypeLabel($format->getDependentLocalityType()),
            'format' => $format->getFormat(),
            'localFormat' => $format->getLocalFormat(),
            'postalCodePattern' => $format->getPostalCodePattern(),
            'postalCodePrefix' => $format->getPostalCodePrefix(),
            'requiredFields' => $format->getRequiredFields(),
            'uppercaseFields' => $format->getUppercaseFields(),
            'usedFields' => $format->getUsedFields(),
            'usedSubdivisionFields' => $format->getUsedSubdivisionFields(),
        ];
    }
}
