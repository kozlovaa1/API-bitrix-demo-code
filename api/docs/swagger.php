<?php

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$swagger = \OpenApi\Generator::scan([$_SERVER['DOCUMENT_ROOT'].'/local/php_interface/src/Controllers']);

header('Content-Type: application/x-yaml');
echo $swagger->toJson();
