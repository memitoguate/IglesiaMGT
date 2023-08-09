<?php

//
//  This code is under copyright not under MIT Licence
//  copyright   : 2022 Philippe Logel all right reserved not MIT licence
//                This code can't be included in another software
//
//  Updated : 2022/01/05
//

namespace EcclesiaCRM\VIEWControllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Base\FamilyQuery;
use EcclesiaCRM\Base\ListOptionQuery;
use EcclesiaCRM\PersonQuery;

use Propel\Runtime\ActiveQuery\Criteria;

use EcclesiaCRM\Map\ListOptionIconTableMap;
use EcclesiaCRM\Map\ListOptionTableMap;

use EcclesiaCRM\EventQuery;

use EcclesiaCRM\MyPDO\CalDavPDO;
use EcclesiaCRM\MyPDO\PrincipalPDO;

use Slim\Views\PhpRenderer;

class VIEWMapController {

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function renderMap (ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface {
        $renderer = new PhpRenderer('templates/map/');

        if ( !( SessionUser::getUser()->isShowMapEnabled() || SessionUser::getUser()->belongsToGroup($args['GroupID']) ) ) {
            return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/v2/dashboard');
        }

        if (SystemConfig::getValue('sMapProvider') == 'OpenStreetMap') {
            return $renderer->render($response, 'MapUsingLeaflet.php', $this->renderMapArray($args['GroupID']));
        } else if (SystemConfig::getValue('sMapProvider') == 'GoogleMaps'){
            return $renderer->render($response, 'MapUsingGoogle.php', $this->renderMapArray($args['GroupID']));
        } else if (SystemConfig::getValue('sMapProvider') == 'BingMaps') {
            return $renderer->render($response, 'MapUsingBing.php', $this->renderMapArray($args['GroupID']));
        }
    }

    public function renderMapArray ($iGroupID)
    {
        //Set the page title
        $sPageTitle = _("View on Map");

        $sRootDocument  = SystemURLs::getDocumentRoot();

        // new way to manage events
        // we get the PDO for the Sabre connection from the Propel connection
        // We set the BackEnd for sabre Backends
        $calendarBackend = new CalDavPDO();
        $principalBackend = new PrincipalPDO();
        // get all the calendars for the current user

        $calendars = $calendarBackend->getCalendarsForUser('principals/'.strtolower(SessionUser::getUser()->getUserName()),"displayname",false);

        $eventsArr = [];

        foreach ($calendars as $calendar) {
            // we get all the events for the Cal
            $eventsForCal = $calendarBackend->getCalendarObjects($calendar['id']);

            if ($calendar['present'] == 0 || $calendar['visible'] == 0) {// this ensure the events are present or not
                continue;
            }

            foreach ($eventsForCal as $eventForCal) {
                $evnt = EventQuery::Create()->filterByInActive('false')->findOneById($eventForCal['id']);

                if ($evnt != null && $evnt->getLocation() != '') {
                    $eventsArr[] = $evnt->getID();
                }
            }
        }


        // we can work with the normal locations
        $plotFamily = false;
        //Get the details from DB
        $dirRoleHead = SystemConfig::getValue('sDirRoleHead');

        if ($iGroupID > 0) {// a group
            // security test
            $currentUserBelongToGroup = SessionUser::getUser()->belongsToGroup($iGroupID);

            if ($currentUserBelongToGroup == 0) {
                RedirectUtils::Redirect('v2/dashboard');
            }

            $persons = PersonQuery::create()
                ->filterByDateDeactivated(NULL) // GDPR
                ->usePerson2group2roleP2g2rQuery()
                ->filterByGroupId($iGroupID)
                ->endUse()
                ->find();

            if ($persons->count() > 50) {
                $families = FamilyQuery::create()
                    ->filterByDateDeactivated(NULL) // GDPR
                    ->setDistinct(\EcclesiaCRM\Map\FamilyTableMap::COL_FAM_ID)
                    ->filterByDateDeactivated(null)
                    ->filterByLatitude(0, Criteria::NOT_EQUAL)
                    ->filterByLongitude(0, Criteria::NOT_EQUAL)
                    ->usePersonQuery()
                        ->filterByDateDeactivated(NULL) // GDPR
                        ->usePerson2group2roleP2g2rQuery()
                            ->filterByGroupId($iGroupID)
                        ->endUse()
                        //->filterByFmrId($dirRoleHead)
                    ->endUse()
                    ->find();

                $plotFamily = true;
            }
        } elseif ($iGroupID == 0) {// the Cart
            // group zero means map the cart
            if (!empty($_SESSION['aPeopleCart'])) {

                // old code : really slow and with all the members of a family at the same place
                $persons = PersonQuery::create()
                    ->filterById($_SESSION['aPeopleCart'])
                    ->find();

                if ($persons->count() > 50) {
                    $families = FamilyQuery::create()
                        ->filterByDateDeactivated(NULL) // GDPR
                        ->setDistinct(\EcclesiaCRM\Map\FamilyTableMap::COL_FAM_ID)
                        ->filterByDateDeactivated(null)
                        ->filterByLatitude(0, Criteria::NOT_EQUAL)
                        ->filterByLongitude(0, Criteria::NOT_EQUAL)
                        ->usePersonQuery()
                            ->filterByDateDeactivated(NULL) // GDPR
                            ->filterById($_SESSION['aPeopleCart'])
                        //->filterByFmrId($dirRoleHead)
                        ->endUse()
                        ->find();

                    $plotFamily = true;
                }
            }
        } elseif ($iGroupID == -1) {// the Family
            //Map all the families
            $families = FamilyQuery::create()
                ->filterByDateDeactivated(NULL) // GDPR
                ->filterByLatitude(0, Criteria::NOT_EQUAL)
                ->filterByLongitude(0, Criteria::NOT_EQUAL)
                ->usePersonQuery('per')
                    ->filterByDateDeactivated(NULL) // GDPR
                    ->filterByFmrId($dirRoleHead)
                ->endUse()
                ->find();

            $plotFamily = true;
        }

        //Markericons list
        $icons = ListOptionQuery::create()
            ->filterById(1)
            ->orderByOptionSequence()
            ->addJoin(ListOptionTableMap::COL_LST_OPTIONID,ListOptionIconTableMap::COL_LST_IC_LST_OPTION_ID,Criteria::LEFT_JOIN)
            ->addAsColumn('url',ListOptionIconTableMap::COL_LST_IC_LST_URL)
            ->addAsColumn('onlyInPersonView',ListOptionIconTableMap::COL_LST_IC_ONLY_PERSON_VIEW)
            ->find();


        $paramsArguments = ['sRootPath'    => SystemURLs::getRootPath(),
            'sRootDocument' => $sRootDocument,
            'sPageTitle'    => $sPageTitle,
            'iGroupID'      => $iGroupID,
            'icons'         => $icons,
            'persons'       => $persons,
            'plotFamily'    => $plotFamily,
            'eventsArr'     => $eventsArr,
            'families'      => $families];

        return $paramsArguments;
    }
}
