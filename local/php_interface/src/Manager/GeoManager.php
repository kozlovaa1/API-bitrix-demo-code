<?php

namespace Manager;

use Bitrix\Iblock\Elements\ElementCitiesTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Uri;
use Bitrix\Sale\Location\LocationTable;
use Local\Response\ErrorResponse;

class GeoManager
{
    /**
     * @throws LoaderException
     */
    public function __construct()
    {
        Loader::includeModule('sale');
        Loader::includeModule('iblock');
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getCity($filter)
    {
        return ElementCitiesTable::getList([
            'select' => ['ID', 'NAME', 'XML_ID', 'SUBDOMAIN_' => 'SUBDOMAIN', 'PHONE_' => 'PHONE'],
            'filter' => $filter,
            "cache" => ["ttl" => 36000],
        ])->fetch();
    }
    public static function getCities($filter)
    {
        return ElementCitiesTable::getList([
            'select' => ['ID', 'NAME', 'XML_ID', 'SUBDOMAIN_' => 'SUBDOMAIN', 'PHONE_' => 'PHONE'],
            'filter' => $filter,
            "cache" => ["ttl" => 36000],
        ])->fetchAll();
    }

    /**
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getRegion($cityId)
    {
        $regionsRes = LocationTable::getList(array(
            'filter' => array(
                '=TYPE.CODE' => 'REGION',
                '=NAME.LANGUAGE_ID' => 'ru',
                '=PARENT.NAME.LANGUAGE_ID' => 'ru'
            ),
            'select' => array('NAME_RU' => 'NAME.NAME', 'ID'),
            "cache" => ["ttl" => 36000],
        ));
        $regions = [];
        while ($region = $regionsRes->fetch()) {
            $regions[$region['ID']] = $region['NAME_RU'];
        }
        $city = LocationTable::getList(array(
            'filter' => array('ID' => $cityId),
            'select' => array('REGION_ID'),
            "cache" => ["ttl" => 36000],
        ))->fetch();
        return $regions[$city['REGION_ID']];
    }
}