<?php

use Bitrix\Main\EventManager;
use Local\Service\GeoService;

EventManager::getInstance()->addEventHandler(
    "main",
    'OnProlog',
    function () {
        (new GeoService())->setCity();
    }
);