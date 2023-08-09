<?php

//
//  This code is under copyright not under MIT Licence
//  copyright   : 2022 Philippe Logel all right reserved not MIT licence
//                This code can't be included in another software
//
//  Updated : 2023/05/19
//

namespace EcclesiaCRM\VIEWControllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;


use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\InputUtils;

use Slim\Views\PhpRenderer;

class VIEWSystemController {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function integritycheck (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        //Set the page title
        if (!SessionUser::getUser()->isAdmin()) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        return $renderer->render($response, 'integritycheck.php', $this->argumentsIntegrityCheckArray());
    }

    public function argumentsIntegrityCheckArray ()
    {
        //Set the page title
        $sPageTitle    = _('Integrity Check Results');
        

        $sRootDocument  = SystemURLs::getDocumentRoot();
        $CSPNonce       = SystemURLs::getCSPNonce();

        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => $sRootDocument,
            'CSPNonce'                  => $CSPNonce,
            'sPageTitle'                => $sPageTitle
        ];

        return $paramsArguments;
    }


    public function reportList (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        //Set the page title
        if ( !( SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance') || SystemConfig::getBooleanValue('bEnabledSundaySchool') ) ) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        return $renderer->render($response, 'reportlist.php', $this->argumentsReportListArray());
    }

    public function argumentsReportListArray ()
    {
        //Set the page title
        $sPageTitle    = _('Report Menu');
        
        $sRootDocument  = SystemURLs::getDocumentRoot();
        $CSPNonce       = SystemURLs::getCSPNonce();

        $today = getdate();
        $year = $today['year'];


        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => $sRootDocument,
            'CSPNonce'                  => $CSPNonce,
            'sPageTitle'                => $sPageTitle,
            'today'                     => $today,
            'year'                      => $year
        ];

        return $paramsArguments;
    }

    public function optionManager (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        $mode = '';
        if (isset($args['mode'])) {
            $mode = InputUtils::LegacyFilterInput($args['mode']);
        }

        $listID = 0;
        if (isset($args['ListID'])) {
            $listID = InputUtils::LegacyFilterInput($args['ListID'], 'int');;
        }
        
        return $renderer->render($response, 'optionManager.php', $this->argumentsOptionManagerArray($mode, $listID));
    }

    public function argumentsOptionManagerArray ($mode, $listID)
    {
        //Set the page title
        $sPageTitle    = _('System');
        
        $sRootDocument  = SystemURLs::getDocumentRoot();
        $CSPNonce       = SystemURLs::getCSPNonce();

        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => $sRootDocument,
            'CSPNonce'                  => $CSPNonce,
            'sPageTitle'                => $sPageTitle,
            'mode'                      => $mode,
            'listID'                    => $listID
        ];

        return $paramsArguments;
    }

    public function convertIndividualToAddress (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        if (!SessionUser::getUser()->isAdmin()) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        $all = 'False';
        if (isset($args['all'])) {
            $all = InputUtils::LegacyFilterInput($args['all']);
        }
        
        
        return $renderer->render($response, 'convertIndividualToAddress.php', $this->argumentsIndividualToAddressArray($all));
    }

    public function argumentsIndividualToAddressArray ($all)
    {
        //Set the page title
        $sPageTitle    = _('Convert Individuals to Addresses');
        
        $sRootDocument  = SystemURLs::getDocumentRoot();
        $CSPNonce       = SystemURLs::getCSPNonce();

        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => $sRootDocument,
            'CSPNonce'                  => $CSPNonce,
            'sPageTitle'                => $sPageTitle,
            'all'                       => $all
        ];

        return $paramsArguments;
    }

    public function csvExport (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        if (!SessionUser::getUser()->isCSVExportEnabled()) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        $Source = '';
        if (isset($args['Source'])) {
            $Source = InputUtils::LegacyFilterInput($args['Source']);
        }
        
        return $renderer->render($response, 'csvExport.php', $this->argumentsCSVExportArray($Source));
    }

    public function argumentsCSVExportArray ($Source)
    {
        //Set the page title
        $sPageTitle    = _('CSV Export');
        
        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => SystemURLs::getDocumentRoot(),
            'CSPNonce'                  => SystemURLs::getCSPNonce(),
            'sPageTitle'                => $sPageTitle,
            'Source'                       => $Source
        ];

        return $paramsArguments;
    }

    public function eventAttendance (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        if (!SystemConfig::getBooleanValue('bEnabledSundaySchool')) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        $Action = '';
        if (isset($args['Action'])) {
            $Action = InputUtils::LegacyFilterInput($args['Action']);
        }

        $Event = -1;
        if (isset($args['Event'])) {
            $Event = InputUtils::LegacyFilterInput($args['Event'], 'int');
        }

        $Type = '';
        if (isset($args['Type'])) {
            $Type = InputUtils::LegacyFilterInput($args['Type']);
        }

        $Choice = '';
        if (isset($args['Choice'])) {
            $Choice = InputUtils::LegacyFilterInput($args['Choice']);
        }      

        return $renderer->render($response, 'eventAttendance.php', $this->argumentsEventAttendanceArray($Action, $Event, $Type, $Choice));
    }

    public function argumentsEventAttendanceArray ($Action, $Event, $Type, $Choice)
    {
        //Set the page title
        $sPageTitle    = _('CSV Export');
        
        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => SystemURLs::getDocumentRoot(),
            'CSPNonce'                  => SystemURLs::getCSPNonce(),
            'sPageTitle'                => $sPageTitle,
            'Action'                    => $Action,
            'Event'                     => $Event,
            'Type'                      => $Type,
            'Choice'                    => $Choice
        ];

        return $paramsArguments;
    }

    

    public function USISTAddressVerification (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $renderer = new PhpRenderer('templates/system/');

        if (!SessionUser::getUser()->isAdmin()) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        $DoLookup = '';
        if (isset($args['DoLookup'])) {
            $DoLookup = InputUtils::LegacyFilterInput($args['DoLookup']);
        }  

        return $renderer->render($response, 'USISTAddressVerification.php', $this->argumentsUSISTAddressVerificationArray($DoLookup));
    }

    public function argumentsUSISTAddressVerificationArray ($DoLookup)
    {
        //Set the page title
        $sPageTitle = _('US Address Verification');
        
        $paramsArguments = ['sRootPath' => SystemURLs::getRootPath(),
            'sRootDocument'             => SystemURLs::getDocumentRoot(),
            'CSPNonce'                  => SystemURLs::getCSPNonce(),
            'sPageTitle'                => $sPageTitle,
            'DoLookup'                  => $DoLookup
        ];

        return $paramsArguments;
    }
    
}
