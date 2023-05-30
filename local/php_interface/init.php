<?php

require(__DIR__ . "/vendor/autoload.php");
require(__DIR__ . "/eventHandlers.php");

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
// You can also load several files
$dotenv->load(__DIR__ . '/../../.env');