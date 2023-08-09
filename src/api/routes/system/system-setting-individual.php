<?php

use Slim\Routing\RouteCollectorProxy;


/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use EcclesiaCRM\APIControllers\SystemSettingsIndividualController;

$app->group('/settingsindividual', function (RouteCollectorProxy $group) {

    /*
     * @! Get 2FA key
     */
    $group->post('/get2FA', SystemSettingsIndividualController::class . ':get2FA' );
    /*
     * @! Verify 2FA
     * #! param: ref->string :: code
     */
    $group->post('/verify2FA', SystemSettingsIndividualController::class . ':verify2FA' );
    /*
     * @! Remove 2FA for session user
     */
    $group->post('/remove2FA', SystemSettingsIndividualController::class . ':remove2FA' );

});


