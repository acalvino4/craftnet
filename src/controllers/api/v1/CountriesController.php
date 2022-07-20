<?php

namespace craftnet\controllers\api\v1;

use CommerceGuys\Addressing\AddressFormat\AddressFormat;
use CommerceGuys\Addressing\Subdivision\Subdivision;
use Craft;
use craft\elements\Address;
use craftnet\controllers\api\BaseApiController;
use Moccalotto\Eu\CountryInfo;
use yii\web\Response;

/**
 * Class CountriesController
 */
class CountriesController extends BaseApiController
{
    protected const COUNTRY_CACHE_KEY = 'countryListData';
    protected const FORMAT_CACHE_KEY_PREFIX = 'formatData';
    protected const COUNTRY_CACHE_DURATION = 60 * 60 * 24 * 7;
    protected const FORMAT_CACHE_DURATION = 60 * 60 * 24 * 7;

    protected $checkCraftHeaders = false;

    /**
     * Handles /v1/countries requests.
     *
     * @return Response
     */
    public function actionIndex(): Response
    {
        $countryList = $this->_getCountryList();

        return $this->asJson([
            'countries' => $countryList,
        ]);
    }

    /**
     * Return a country list populated with state info.
     *
     * @return array
     */
    private function _getCountryList(): array
    {
        $cache = Craft::$app->getCache();

        if ($cache->exists(self::COUNTRY_CACHE_KEY)) {
            return $cache->get(self::COUNTRY_CACHE_KEY);
        }

        $countries = Craft::$app->getAddresses()->getCountryRepository()->getAll();
        $countryList = [];
        $countryInfo = new CountryInfo();

        foreach ($countries as $country) {
            $administrativeAreas = Craft::$app->getAddresses()->getSubdivisionRepository()->getList([$country->getCountryCode()]);
            $isStateRequired = !empty($administrativeAreas);
            $countryData = [
                'name' => $country->getName(),
                'euMember' => $countryInfo->isEuMember($country->getCountryCode()),
                'stateRequired' => $isStateRequired,
                'administrativeAreaLabel' => Address::addressAttributeLabel('administrativeArea', $country->getCountryCode())
            ];

            if (!empty($administrativeAreas)) {
                $countryData['states'] = $administrativeAreas;
            }

            $countryList[$country->getCountryCode()] = $countryData;
        }

        $cache->set(self::COUNTRY_CACHE_KEY, $countryList, self::COUNTRY_CACHE_DURATION);

        return $countryList;
    }

    /**
     * @return Response
     */
    public function actionFormat(): Response
    {
        $subdivisionRepo = Craft::$app->getAddresses()->getSubdivisionRepository();
        $addressFormatRepo = Craft::$app->getAddresses()->getAddressFormatRepository();
        $cache = Craft::$app->getCache();

        $data = [];

        $parents = Craft::$app->getRequest()->getParam('parents');
        if (!$parents) {
            return $this->asJson($data);
        }

        $cacheKey = self::FORMAT_CACHE_KEY_PREFIX . '|' . join('|', $parents);

        if ($cache->exists($cacheKey)) {
            return $this->asJson($cache->get($cacheKey));
        }

        // First item must always be a country
        $data['format'] = $this->_formatAsArray($addressFormatRepo->get(collect($parents)->first()));
        $data['subdivisions'] = collect($subdivisionRepo->getAll($parents))->map(function ($subdivision) {
            return $this->_subdivisionsAsArray($subdivision);
        });

        $cache->set($cacheKey, $data, self::FORMAT_CACHE_DURATION);

        return $this->asJson($data);
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
            'children' => collect($subdivision->getChildren())->map(function ($subdivision) {
                return $this->_subdivisionsAsArray($subdivision);
            })
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
            'administrativeArea' => $format->getAdministrativeAreaType(),
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
