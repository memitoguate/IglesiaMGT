<?php

require '../Include/Config.php';

// This file is generated by Composer
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\KioskDevice;
use EcclesiaCRM\KioskDeviceQuery;

use EcclesiaCRM\Slim\Middleware\VersionMiddleware;

// Instantiate the app
//$settings = require __DIR__ . '/settings.php';
$container = new Container();

$settings = require __DIR__.'/../Include/slim/settings.php';
$settings($container);

$rootPath = str_replace('/kiosk/index.php', '', $_SERVER['SCRIPT_NAME']);

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->setBasePath($rootPath . "/kiosk");

$app->add( new VersionMiddleware() );

// Set up
require __DIR__ . '/../Include/slim/error-handler.php';

// routes
require __DIR__ . '/routes/kiosk.php';

$windowOpen = new DateTime(SystemConfig::getValue("sKioskVisibilityTimestamp")) > new DateTime();

if ( isset($_COOKIE['kioskCookie']) ) {
    $g = hash('sha256', $_COOKIE['kioskCookie']);
    $Kiosk =  KioskDeviceQuery::create()
          ->findOneByGUIDHash($g);
    if (is_null($Kiosk)) {
        setcookie('kioskCookie', '', time() - 3600);
        header('Location: '.$_SERVER['REQUEST_URI']);
    }
}

if (!isset($_COOKIE['kioskCookie'])) {
    if ($windowOpen) {
        $guid = uniqid();
        setcookie("kioskCookie", $guid, 2147483647);
        $Kiosk = new KioskDevice();
        $Kiosk->setGUIDHash(hash('sha256', $guid));
        $Kiosk->setAccepted(false);
        $Kiosk->save();
    } else {
        header("HTTP/1.1 401 Unauthorized");
        exit;
    }
}
$app->kiosk = $Kiosk;

// Run app
$app->run();
