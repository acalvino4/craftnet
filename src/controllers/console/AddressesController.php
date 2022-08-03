<?php

namespace craftnet\controllers\console;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\Subdivision\Subdivision;
use Craft;
use craft\elements\Address;
use craftnet\controllers\api\BaseApiController;
use Illuminate\Support\Collection;
use Moccalotto\Eu\CountryInfo;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Class AddressesController
 */
class AddressesController extends BaseController
{
    protected const FORMAT_CACHE_KEY_PREFIX = 'formatData';
    protected const FORMAT_CACHE_DURATION = 60 * 60 * 24 * 7;

    /**
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionGetAddressInfo(): Response
    {
        $this->requireAcceptsJson();
        $subdivisionRepo = Craft::$app->getAddresses()->getSubdivisionRepository();
        $addressFormatRepo = Craft::$app->getAddresses()->getAddressFormatRepository();
        $cache = Craft::$app->getCache();
        $parents = Craft::$app->getRequest()->getRequiredParam('parents');

        if (!is_array($parents) || empty($parents)) {
            throw new BadRequestHttpException();
        }

        $cacheKey = [self::FORMAT_CACHE_KEY_PREFIX => $parents];

        // First item must always be a country
        $country = $addressFormatRepo->get(Collection::make($parents)->first());
        $subdivisions = Collection::make($subdivisionRepo->getAll($parents));
        $addressInfo = $cache->getOrSet($cacheKey, fn() => [
            'format' => $this->_formatAsArray($country),
            'subdivisions' => $subdivisions->map(fn($subdivision) => $this->_subdivisionsAsArray($subdivision)),
        ], self::FORMAT_CACHE_DURATION);

        return $this->asSuccess(data: ['addressInfo' => $addressInfo]);
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
