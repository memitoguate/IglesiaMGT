<?php

/*******************************************************************************
 *
 *  filename    : route/backup.php
 *  last change : 2019-11-21
 *  description : manage the backup
 *
 *  http://www.ecclesiacrm.com/
 *
 *  This code is under copyright not under MIT Licence
 *  copyright   : 2018 Philippe Logel all right reserved not MIT licence
 *                This code can't be incorporated in another software authorization
 *
 ******************************************************************************/

use Slim\Routing\RouteCollectorProxy;

use EcclesiaCRM\VIEWControllers\VIEWQueryController;

$app->group('/query', function (RouteCollectorProxy $group) {
    $group->get('/list', VIEWQueryController::class . ':querylist');
    
    $group->get('/view/{queryID:[0-9]+}', VIEWQueryController::class . ':queryview');
    $group->post('/view/{queryID:[0-9]+}', VIEWQueryController::class . ':queryview');

    $group->get('/sql', VIEWQueryController::class . ':querysql');
    $group->post('/sql', VIEWQueryController::class . ':querysql');
});
