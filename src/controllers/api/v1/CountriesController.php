<?php

namespace craftnet\controllers\api\v1;

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
    protected const COUNTRY_CACHE_DURATION = 60 * 60 * 24 * 7;

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
}
