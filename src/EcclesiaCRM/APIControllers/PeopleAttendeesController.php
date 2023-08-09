<?php

//
//  This code is under copyright not under MIT Licence
//  copyright   : 2021 Philippe Logel all right reserved not MIT licence
//                This code can't be included in another software
//
//  Updated : 2021/04/06
//

namespace EcclesiaCRM\APIControllers;

use EcclesiaCRM\Utils\InputUtils;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use EcclesiaCRM\ListOptionQuery;
use EcclesiaCRM\EventAttendQuery;
use EcclesiaCRM\EventAttend;
use EcclesiaCRM\EventTypesQuery;
use EcclesiaCRM\EventQuery;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\GroupQuery;
use EcclesiaCRM\dto\SystemConfig;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\Utils\OutputUtils;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\EventCountNameQuery;
use EcclesiaCRM\EventCountsQuery;
use EcclesiaCRM\EventCounts;

use EcclesiaCRM\CalendarinstancesQuery;

use EcclesiaCRM\MyVCalendar\VCalendarExtension;

use EcclesiaCRM\MyPDO\CalDavPDO;

class PeopleAttendeesController
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function qrcodeCallAttendees(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->groupID) && isset ($requestValues->personID)) {
            $person = PersonQuery::create()->findOneById($requestValues->personID);

            $group = GroupQuery::create()->findOneById($requestValues->groupID);

            $Event = EventQuery::create()
                ->filterByStart('now', Criteria::LESS_EQUAL)
                ->filterByEnd('now', Criteria::GREATER_EQUAL)
                ->_and()->filterByGroupId((int)$requestValues->groupID)
                ->findOne();

            if (is_null($Event)) {
                $Event = EventQuery::create()
                    ->filterByGroupId((int)$requestValues->groupID)
                    ->findOneById($_SESSION['EventID']);

                if (is_null($Event)) {
                    return $response->withJson(['status' => "failed", 'person' => $person->getFullName(), 'group' => $group->getName()]);
                }
            }

            $eventAttent = EventAttendQuery::Create()
                ->filterByEventId((int)$Event->getId())
                ->filterByPersonId((int)$requestValues->personID)
                ->findOne();

            $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));

            $returnData = "";

            if (!is_null($eventAttent)) {
                $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                if ( is_null($eventAttent->getPersonId()) ) {
                    $eventAttent->getEvent()->checkInPerson($requestValues->personID);
                } else {
                    if ( is_null($eventAttent->getCheckinDate() ) ) {
                        $eventAttent->setCheckinDate(date('Y-m-d H:i:s'))
                            ->setCheckoutDate(null)
                            ->save();
                    } else {
                        $eventAttent->setCheckoutDate(date('Y-m-d H:i:s'))
                            ->save();
                    }
                }
                $returnData = OutputUtils::FormatDate($date->format('Y-m-d H:i:s'), 1);
                $eventAttent->save();
            }

            if ( !is_null($person) and !is_null($group) ) {
                return $response->withJson(['status' => "success", 'person' => $person->getFullName(), 'group' => $group->getName(), 'data' => $returnData]);
            }
        }

        return $response->withJson(['status' => "global_failed"]);
    }

    public function addFreeAttendees(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->eventID) && isset ($requestValues->fieldText) && isset($requestValues->counts)) {

            $logger = $this->container->get('Logger');
            $logger->info(print_r((array)$requestValues->counts, 1));

            $event = EventQuery::Create()
                ->findOneById($requestValues->eventID);

            $eventCountNames = EventCountNameQuery::Create()
                ->leftJoinEventTypes()
                ->Where('type_id=' . $event->getType())
                ->find();

            $eventCounts = EventCountsQuery::Create()
                ->findByEvtcntEventid($requestValues->eventID);

            if (!empty($eventCounts)) {
                $eventCounts->delete();
            }

            foreach ($eventCountNames as $eventCountName) {
                $eventCount = new EventCounts;
                $eventCount->setEvtcntEventid($requestValues->eventID);
                $eventCount->setEvtcntCountid($eventCountName->getId());
                $eventCount->setEvtcntCountname($eventCountName->getName());
                $eventCount->setEvtcntCountcount($requestValues->counts[$eventCountName->getId()]);
                $eventCount->setEvtcntNotes($requestValues->fieldText);
                $eventCount->save();
            }
        }

        return $response->withJson(['status' => "failed"]);

    }

    public function checkoutValidateAttendees(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $requestValues = (object)$request->getParsedBody();

        if ( isset ($requestValues->eventID) ) {
            $_SESSION['EventID'] = $requestValues->eventID;

            return $response->withJson(['status' => "success"]);
        }

        return $response->withJson(['status' => "failed"]);
    }

    public function validateAttendees(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {

        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->eventID) && isset ($requestValues->noteText)) {
            $event = EventQuery::Create()
                ->findOneById($requestValues->eventID);

            $event->setText(InputUtils::FilterHTML($requestValues->noteText));

            $event->save();


            $eventAttents = EventAttendQuery::Create()
                ->filterByEventId($requestValues->eventID)
                ->find();

            $res['button'] = "success";

            $stats = [
                'total' => $eventAttents->count(),
                'checkcout' => 0,
                'difference' => $eventAttents->count()
            ];

            foreach ($eventAttents as $eventAttent) {
                if ( $eventAttent->getCheckinId() > 0 and !is_null($eventAttent->getCheckinDate()) and is_null($eventAttent->getCheckoutDate()) ) {
                    $eventAttent->setCheckoutDate(date('Y-m-d H:i:s'));
                    $stats['checkcout']++;
                    $stats['difference']--;

                    $res['button'] = "danger";
                } else if ( $eventAttent->getCheckinId() > 0 and !is_null($eventAttent->getCheckinDate()) and !is_null($eventAttent->getCheckoutDate()) ) {
                    $eventAttent->setCheckoutDate(NULL);
                }

                $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());

                $eventAttent->save();
            }

            $res['stats'] = $stats;

            return $response->withJson(['status' => "success", "results" => $res]);
        }

        return $response->withJson(['status' => "failed"]);
    }

    public function deleteAttendeesPerson(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->personID) && isset ($requestValues->eventID)) {
            $attendDel = EventAttendQuery::create()
                ->filterByEventId($requestValues->eventID)
                ->findOneByPersonId($requestValues->personID);
            if (!empty($attendDel)) {
                $attendDel->delete();
            }

            return $response->withJson(['status' => "success"]);
        }

        return $response->withJson(['status' => "failed"]);
    }

    public function addAttendeesPerson(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if ((isset ($requestValues->iChildID) || isset ($requestValues->iAdultID)) && isset ($requestValues->eventID)) {
            $attendee = EventAttendQuery::create()->filterByEventId($requestValues->eventID)->findOneByPersonId($requestValues->iChildID);
            if ($attendee) {
                return $response->withJson(['status' => "failed"]);
            } else {
                $attendee = new EventAttend();
                $attendee->setEventId($requestValues->eventID);
                $attendee->setPersonId($requestValues->iChildID);
                $attendee->setCheckinId(SessionUser::getId());
                $attendee->setCheckinDate(date("Y-m-d H:i:s"));
                $attendee->save();

                if (isset ($requestValues->iAdultID)) {
                    $attendee = new EventAttend();
                    $attendee->setEventId($requestValues->eventID);
                    $attendee->setPersonId($requestValues->iAdultID);
                    $attendee->setCheckinId(SessionUser::getId());
                    $attendee->setCheckinDate(date("Y-m-d H:i:s"));
                    $attendee->save();
                }
            }

            return $response->withJson(['status' => "success"]);
        }

        return $response->withJson(['status' => "failed"]);
    }

    public function attendeesEvent(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (!( array_key_exists('eventID', $args) )) {
            return $response->withStatus(401);
        }

        $eventID = $args['eventID'];

        $eventAttendees = EventAttendQuery::create()
            ->joinWithPerson()
            ->usePersonQuery()
            ->orderByFirstName()
            ->endUse()
            ->findByEventId($eventID);


        $event = EventQuery::create()->findOneById($eventID);

        $groupID = $event->getGroupId();

        $group = GroupQuery::create()->findOneById($groupID);

        $bSundaySchool = false;

        if (!is_null($group)) {
            $bSundaySchool = $group->isSundaySchool();
        }

        if ($bSundaySchool) {
            $genderMale = _("Boy");
            $genderFem = _("Girl");
        } else {
            $genderMale = _("Man");
            $genderFem = _("Woman");
        }


        $result = [];

        foreach ($eventAttendees as $per) {
            $item = [];

            $item['Id'] = $per->getPersonId();

            //Get Person who is checked in
            $checkedInPerson = PersonQuery::create()
                ->findOneById($per->getPersonId());

            $item['Gender'] = ($checkedInPerson->getGender() == 1) ? $genderMale : $genderFem;
            $item['FirstName'] = $checkedInPerson->getFirstName();
            $item['LastName'] = $checkedInPerson->getLastName();
            $item['checkinDate'] = (!empty($per->getCheckinDate())) ? OutputUtils::FormatDate($per->getCheckinDate()->format("Y-m-d H:i:s"), 1) : "";
            $item['isCheckinDate'] = (!is_null($per->getCheckinDate())) ? "checked" : "";
            $item['checkoutDate'] = (!empty($per->getCheckoutDate())) ? OutputUtils::FormatDate($per->getCheckoutDate()->format("Y-m-d H:i:s"), 1) : "";
            $item['isCheckoutDate'] = (!is_null($per->getCheckoutDate())) ? "checked" : "";

            if (is_null($checkedInPerson)) {// we have to avoid pure user and not persons
                continue;
            }

            $item['sPerson'] = $checkedInPerson->getFullName();

            //Get Person who checked person in
            $item['checkinby'] = "";
            if ($per->getCheckinId()) {
                $checkedInBy = PersonQuery::create()
                    ->findOneById($per->getCheckinId());
                if (!is_null($checkedInBy)) {
                    $item['checkinby'] = $checkedInBy->getFullName();
                }
            }

            //Get Person who checked person out
            $item['checkoutby'] = "";
            if ($per->getCheckoutId()) {
                $checkedOutBy = PersonQuery::create()
                    ->findOneById($per->getCheckoutId());
                $item['checkoutby'] = $checkedOutBy->getFullName();
            }

            $result[] = $item;
        }

        return $response->withJson(["CheckinCheckoutEvents" => $result]);
    }

    public function attendeesCheckIn(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if ( isset ($requestValues->personID) && isset ($requestValues->eventID) && isset($requestValues->checked) ) {
            $eventAttent = EventAttendQuery::Create()
                ->filterByEventId($requestValues->eventID)
                ->filterByPersonId($requestValues->personID)
                ->findOne();

            $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));

            $returnData = "";

            if ($eventAttent) {
                $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                if ($requestValues->checked) {
                    $eventAttent->getEvent()->checkInPerson($requestValues->personID);
                    $returnData = OutputUtils::FormatDate($date->format('Y-m-d H:i:s'), 1);
                } else {
                    $eventAttent->getEvent()->unCheckInPerson($requestValues->personID);
                }
                $eventAttent->save();
            }
        } else {
            throw new \Exception(_("POST to cart requires a personID and an eventID"), 500);
        }
        $person = PersonQuery::Create()->findOneById(SessionUser::getUser()->getPersonId());

        return $response->withJson(['status' => "success", "name" => $person->getFullName(), "date" => $returnData]);
    }

    public function attendeesCheckOut(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->personID) && isset ($requestValues->eventID) && isset($requestValues->checked)) {
            $eventAttent = EventAttendQuery::Create()
                ->filterByEventId($requestValues->eventID)
                ->filterByPersonId($requestValues->personID)
                ->findOne();

            $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));

            $returnData = "";

            if (!is_null($eventAttent)) {
                $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                if ($requestValues->checked) {
                    $eventAttent->getEvent()->checkOutPerson($requestValues->personID);
                    $returnData = OutputUtils::FormatDate($date->format('Y-m-d H:i:s'), 1);
                } else {
                    $eventAttent->getEvent()->unCheckOutPerson($requestValues->personID);
                }
                $eventAttent->save();
            }
        } else {
            throw new \Exception(_("POST to cart requires a personID and an eventID"), 500);
        }
        $person = PersonQuery::Create()->findOneById(SessionUser::getUser()->getPersonId());

        return $response->withJson(['status' => "success", "name" => $person->getFullName(), "date" => $returnData]);
    }

    public function attendeesStudent(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->eventTypeID) && isset ($requestValues->groupID) && isset($requestValues->rangeInHourseventTypeID) && isset ($requestValues->groupID) && isset($requestValues->rangeInHours)) {
            $group = GroupQuery::Create()
                ->findOneById($requestValues->groupID);

            $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));

            $dateTime_End = new \DateTime($requestValues->dateTime);

            $interval = new \DateInterval("PT" . $requestValues->rangeInHours . "H");

            $dateTime_End->add($interval);

            $type = null;

            if ($requestValues->eventTypeID) {
                $type = EventTypesQuery::Create()
                    ->findOneById($requestValues->eventTypeID);
                $eventTypeName = $type->getName();
            }

            $event = EventQuery::Create()
                ->filterByGroupId($requestValues->groupID)
                ->filterByInActive(1, Criteria::NOT_EQUAL)
                ->Where('YEAR(event_start)=' . $date->format('Y') . ' AND MONTH(event_start)=' . $date->format('m') . ' AND Day(event_start)=' . $date->format('d'))// We filter only the events from the current month : date('Y')
                ->findOne();

            if (!is_null($event)) {
                $_SESSION['Action'] = 'Add';
                $_SESSION['EID'] = $event->getID();
                $_SESSION['EName'] = $event->getTitle();
                $_SESSION['EDesc'] = $event->getDesc();
                $_SESSION['EDate'] = $event->getStart();
                $_SESSION['EventID'] = $event->getID();
            } else {
                // new way to manage events : sabre

                // We set the BackEnd for sabre Backends
                $calendarBackend = new CalDavPDO();

                $uuid = strtoupper(\Sabre\DAV\UUIDUtil::getUUID());

                $vcalendar = new VCalendarExtension();

                $vcalendar->add(
                    'VEVENT', [
                    'CREATED' => (new \DateTime('Now'))->format('Ymd\THis'),
                    'DTSTAMP' => (new \DateTime('Now'))->format('Ymd\THis'),
                    'DTSTART' => ($date)->format('Ymd\THis'),
                    'DTEND' => ($dateTime_End)->format('Ymd\THis'),
                    'LAST-MODIFIED' => (new \DateTime('Now'))->format('Ymd\THis'),
                    'DESCRIPTION' => _("Create From sunday school class view"),
                    'SUMMARY' => $group->getName() . " " . $date->format(SystemConfig::getValue('sDatePickerFormat')),
                    'UID' => $uuid,
                    'SEQUENCE' => '0',
                    'TRANSP' => 'OPAQUE'
                ]);


                $calendar = CalendarinstancesQuery::Create()->findOneByGroupId($group->getId());

                $etag = $calendarBackend->createCalendarObject([$calendar->getCalendarid(), $calendar->getId()], $uuid, $vcalendar->serialize());

                $event = EventQuery::Create()->findOneByEtag(str_replace('"', "", $etag));

                $event->setTitle($group->getName() . " " . $date->format(SystemConfig::getValue('sDatePickerFormat')));

                if (!is_null($type)) {
                    $event->setType($type->getId());
                    $event->setTypeName($type->getName());
                }

                $event->setDesc(_("Create From sunday school class view"));
                $event->setStart($date->format('Y-m-d H:i:s'));
                $event->setEnd($dateTime_End->format('Y-m-d H:i:s'));
                $event->setText(_("Attendance"));
                $event->setGroupId($group->getId());
                $event->setInActive(false);
                $event->setCreatorUserId(SessionUser::getId());
                $event->save();

                $sundaySchoolService = $this->container->get('SundaySchoolService');
                $thisClassChildren = $sundaySchoolService->getKidsFullDetails($requestValues->groupID);

                foreach ($thisClassChildren as $child) {
                    try {
                        $eventAttent = new EventAttend();
                        $eventAttent->setEventId($event->getID());
                        $eventAttent->setCheckinId(SessionUser::getUser()->getPersonId());
                        $eventAttent->setCheckinDate(NULL);
                        $eventAttent->setPersonId($child['kidId']);

                        if (SystemConfig::getValue("bCheckedAttendees")) {
                            $eventAttent->setCheckoutDate(NULL);
                        }
                        if (SystemConfig::getValue("bCheckedAttendeesCurrentUser")) {
                            $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                        }
                        $eventAttent->save();
                    } catch (\Exception $ex) {
                        $errorMessage = $ex->getMessage();
                    }
                }

                $_SESSION['Action'] = 'Add';
                $_SESSION['EID'] = $event->getID();
                $_SESSION['EName'] = $event->getTitle();
                $_SESSION['EDesc'] = $event->getDesc();
                $_SESSION['EDate'] = $date->format('Y-m-d H:i:s');

                $_SESSION['EventID'] = $event->getID();
            }
        } else {
            throw new \Exception(_("POST to cart requires a EventID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }

    public function attendeesDelete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if ( isset ($requestValues->eventID) and isset($requestValues->personID) ) {
            $eventAttend = EventAttendQuery::Create()->filterByEventId($requestValues->eventID)->filterByPersonId($requestValues->personID)->limit(1)->findOne();
            if ($eventAttend) {
                $eventAttend->delete();
            }
        } else {
            throw new \Exception(_("POST to delete attendees requires a EventID and personID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }

    public function attendeesDeleteAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (isset ($requestValues->eventID)) {
            $eventAttends = EventAttendQuery::Create()->filterByEventId($requestValues->eventID)->find();

            if (!empty($eventAttends)) {
                $eventAttends->delete();
            }
        } else {
            throw new \Exception(_("POST to cart requires a EventID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }

    public function attendeesCheckAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (!( isset ($requestValues->eventID) && isset($requestValues->type) )) {
            return $response->withStatus(401);
        }

        if (isset ($requestValues->eventID) && isset($requestValues->type)) {
            $eventAttents = EventAttendQuery::Create()
                ->filterByEventId($requestValues->eventID)
                ->find();

            $_SESSION['EventID'] = $requestValues->eventID;

            $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));

            foreach ($eventAttents as $eventAttent) {
                $eventAttent->setCheckinId(SessionUser::getUser()->getPersonId());

                if ($requestValues->type == 1) {
                    $eventAttent->setCheckinDate($date->format('Y-m-d H:i:s'));
                } else if ($requestValues->type == 2 && !is_null($eventAttent->getCheckinDate())) {
                    $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                    $eventAttent->setCheckoutDate($date->format('Y-m-d H:i:s'));
                }

                $eventAttent->save();
            }
        } else {
            throw new \Exception(_("POST to cart requires a EventID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }

    public function attendeesUncheckAll(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (!( isset ($requestValues->eventID) && isset($requestValues->type) )) {
            return $response->withStatus(401);
        }

        if (isset ($requestValues->eventID) && isset($requestValues->type)) {
            $eventAttents = EventAttendQuery::Create()
                ->filterByEventId($requestValues->eventID)
                ->find();

            $_SESSION['EventID'] = $requestValues->eventID;


            foreach ($eventAttents as $eventAttent) {
                $eventAttent->setCheckinId(SessionUser::getUser()->getPersonId());
                $eventAttent->setCheckoutId(NULL);

                if ($requestValues->type == 1) {
                    $eventAttent->setCheckinDate(NULL);
                    $eventAttent->setCheckoutDate(NULL);
                } else if ($requestValues->type == 2) {
                    $eventAttent->setCheckoutDate(NULL);
                }

                $eventAttent->save();
            }
        } else {
            throw new \Exception(_("POST to cart requires a EventID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }

    public function attendeesGroups(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $requestValues = (object)$request->getParsedBody();

        if (!( isset ($requestValues->dateTime) && isset ($requestValues->eventTypeID) && isset ($requestValues->rangeInHours) )) {
            return $response->withStatus(401);
        }

        if (isset ($requestValues->dateTime) && isset ($requestValues->eventTypeID) && isset ($requestValues->rangeInHours)) {
            $listOptions = ListOptionQuery::Create()
                ->filterById(3) // the group category
                ->filterByOptionType('sunday_school')
                ->orderByOptionSequence()
                ->find();

            $dateTime = new \DateTime($requestValues->dateTime);

            $dateTime_End = new \DateTime($requestValues->dateTime);

            $interval = new \DateInterval("PT" . $requestValues->rangeInHours . "H");

            $dateTime_End->add($interval);

            foreach ($listOptions as $listOption) {
                $groups = GroupQuery::Create()
                    ->useGroupTypeQuery()
                    ->filterByListOptionId($listOption->getOptionId())
                    ->endUse()
                    ->filterByType(4)// sunday groups
                    ->orderByName()
                    ->find();

                foreach ($groups as $group) {
                    $type = null;

                    if ($requestValues->eventTypeID) {
                        $type = EventTypesQuery::Create()
                            ->findOneById($requestValues->eventTypeID);
                    }

                    $event = EventQuery::Create()
                        ->filterByGroupId($requestValues->groupID)
                        ->filterByInActive(1, Criteria::NOT_EQUAL)
                        ->Where('YEAR(event_start)=' . $dateTime->format('Y') . ' AND MONTH(event_start)=' . $dateTime->format('m') . ' AND Day(event_start)=' . $dateTime->format('d'))// We filter only the events from the current month : date('Y')
                        ->findOne();

                    if (!empty($event)) {
                        $_SESSION['Action'] = 'Add';
                        $_SESSION['EID'] = $event->getID();
                        $_SESSION['EName'] = $event->getTitle();
                        $_SESSION['EDesc'] = $event->getDesc();
                        $_SESSION['EDate'] = $event->getStart();
                        $_SESSION['EventID'] = $event->getID();
                    } else {
                        // new way to manage events : sabre
                        // We set the BackEnd for sabre Backends
                        $calendarBackend = new CalDavPDO();

                        $uuid = strtoupper(\Sabre\DAV\UUIDUtil::getUUID());

                        $vcalendar = new VCalendarExtension();

                        $vcalendar->add(
                            'VEVENT', [
                            'CREATED' => ($dateTime)->format('Ymd\THis'),
                            'DTSTAMP' => ($dateTime)->format('Ymd\THis'),
                            'DTSTART' => ($dateTime)->format('Ymd\THis'),
                            'DTEND' => ($dateTime_End)->format('Ymd\THis'),
                            'LAST-MODIFIED' => (new \DateTime('Now'))->format('Ymd\THis'),
                            'DESCRIPTION' => _("Create From sunday school class view"),
                            'SUMMARY' => $group->getName() . " " . $dateTime->format(SystemConfig::getValue('sDatePickerFormat')),
                            'UID' => $uuid,
                            'SEQUENCE' => '0',
                            'TRANSP' => 'OPAQUE'
                        ]);


                        $calendar = CalendarinstancesQuery::Create()->findOneByGroupId($group->getId());

                        $etag = $calendarBackend->createCalendarObject([$calendar->getCalendarid(), $calendar->getId()], $uuid, $vcalendar->serialize());

                        $event = EventQuery::Create()->findOneByEtag(str_replace('"', "", $etag));

                        $event->setTitle($group->getName());

                        if (!is_null($type)) {
                            $event->setType($type->getId());
                            $event->setTypeName($type->getName());
                        }

                        $event->setDesc(_("Create From sunday school class view"));
                        $event->setStart($dateTime->format('Y-m-d H:i:s'));
                        $event->setEnd($dateTime_End->format('Y-m-d H:i:s'));
                        $event->setText(_("Attendance"));
                        $event->setInActive(false);
                        $event->setCreatorUserId(SessionUser::getId());
                        $event->save();



                        $sundaySchoolService = $this->container->get('SundaySchoolService');
                        $thisClassChildren = $sundaySchoolService->getKidsFullDetails($group->getId());

                        foreach ($thisClassChildren as $child) {
                            try {
                                $eventAttent = new EventAttend();
                                $eventAttent->setEventId($event->getID());
                                $eventAttent->setCheckinId(SessionUser::getUser()->getPersonId());
                                $eventAttent->setPersonId($child['kidId']);

                                if (SystemConfig::getBooleanValue('bCheckedAttendees')) {
                                    $date = new \DateTime('now', new \DateTimeZone(SystemConfig::getValue('sTimeZone')));
                                    $eventAttent->setCheckinDate($date);
                                    $eventAttent->setCheckoutDate(NULL);
                                } else {
                                    $eventAttent->setCheckinDate(NULL);
                                }
                                if (SystemConfig::getValue("bCheckedAttendeesCurrentUser")) {
                                    $eventAttent->setCheckoutId(SessionUser::getUser()->getPersonId());
                                }
                                $eventAttent->save();
                            } catch (\Exception $ex) {
                                $errorMessage = $ex->getMessage();
                            }
                        }

                        $_SESSION['Action'] = 'Add';
                        $_SESSION['EID'] = $event->getID();
                        $_SESSION['EName'] = $event->getTitle();
                        $_SESSION['EDesc'] = $event->getDesc();
                        $_SESSION['EDate'] = $dateTime->format('Y-m-d H:i:s');

                        $_SESSION['EventID'] = $event->getID();
                    }
                }
            }
        } else {
            throw new \Exception(_("POST to cart requires an EventID"), 500);
        }
        return $response->withJson(['status' => "success"]);
    }
}
