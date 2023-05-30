<?php

namespace Service;

use Bitrix\Main\Application;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Loader;
use Bitrix\Main\Service\GeoIp;
use Bitrix\Main\Web\Uri;
use Local\Exception\GeoException;
use Local\Manager\GeoManager;

class GeoService
{

    /**
     * @throws LoaderException
     */
    public function __construct()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('sale');
    }


    /**
     * @throws GeoException
     */
    public function setCity()
    {
        $session = Application::getInstance()->getSession();

        $hostName = explode('.', $_SERVER['HTTP_HOST']);
        $session->set('subdomain', array_shift($hostName));
        $filter = ['=ACTIVE' => 'Y', "SUBDOMAIN.VALUE" => $session['subdomain']];
        $element = GeoManager::getCity($filter);
        $ipAddress = GeoIp\Manager::getRealIp();
        $ipId = \Bitrix\Sale\Location\GeoIp::getLocationId($ipAddress);
        if ($element) {
            $session->set('city', [
                'id' => $element['XML_ID'],
                'name' => $element['NAME'],
                'region' => GeoManager::getRegion($element['XML_ID']),
                'phone' => $element['PHONE_VALUE'],
                'subdomain' => $element['SUBDOMAIN_VALUE'],
                'ip_id' => $ipId
            ]);
            $session->set('test', 'test1');
        } elseif ($session->has('city')) {
            $this->changeCity($ipId);
        }
    }

    /**
     * @throws GeoException
     */
    public function changeCity($cityId): bool
    {
        $session = Application::getInstance()->getSession();
        $request = Application::getInstance()->getContext()->getRequest();
        if (!$request->isAdminSection()) {
            $filter = ['=ACTIVE' => 'Y', "XML_ID" => $cityId];
            if($cityId == 0){
                $filter = ["NAME" => 'Санкт-Петербург'];
            }
            $element = GeoManager::getCity($filter);
            if ($element) {
                $session->set('city', [
                    'id' => $element['XML_ID'],
                    'name' => $element['NAME'],
                    'region' => GeoManager::getRegion($element['XML_ID']),
                    'phone' => $element['PHONE_VALUE'],
                    'subdomain' => $element['SUBDOMAIN_VALUE'],
                    'ip_id' => $cityId
                ]);
                $uri = new Uri('https:'. $request->getRequestUri());
                $uri->setHost($element['SUBDOMAIN_VALUE'] . '.' . SITE_SERVER_NAME);
                $url = $uri->getUri();
                LocalRedirect($url);
            } else {
                throw new GeoException('Город не найден в списке местоположений');
            }
        } else {
            return false;
        }
    }

    /**
     * @throws GeoException
     */
    public function findCities($name)
    {
        $filter = ['=ACTIVE' => 'Y', "NAME" => $name . '%'];
        $elements = GeoManager::getCities($filter);
        if ($elements) {
            return $elements;
        } else {
            throw new GeoException('Город не найден в списке местоположений');
        }
    }
}
