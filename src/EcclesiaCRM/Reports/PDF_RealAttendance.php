<?php
/*******************************************************************************
 *
 *  filename    : Reports/PDF_RealAttendance.php
 *  description : Creates a PDF for a Sunday School Class Attendance List
 *  Udpdated    : 2021-10-12
 *  This code is under copyright not under MIT Licence
 *  copyright   : 2018 Philippe Logel all right reserved not MIT licence
 *                This code can't be included in another software without authorization
 ******************************************************************************/

namespace EcclesiaCRM\Reports;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\EventCounts;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\Utils\LoggerUtils;
use EcclesiaCRM\Utils\OutputUtils;
use EcclesiaCRM\GroupQuery;
use EcclesiaCRM\EventQuery;
use EcclesiaCRM\EventAttendQuery;
use EcclesiaCRM\Record2propertyR2pQuery;
use EcclesiaCRM\PropertyQuery;
use EcclesiaCRM\EventCountNameQuery;
use EcclesiaCRM\EventCountsQuery;

use EcclesiaCRM\Map\PersonTableMap;
use Propel\Runtime\ActiveQuery\Criteria;

use EcclesiaCRM\ListOptionQuery;

class PDF_RealAttendance extends PDF_Attendance
{
    protected $groupIDs;
    protected $withPictures;
    protected $iExtraStudents;
    protected $iFYID;
    protected $startDate;
    protected $endDate;

    // Constructor
    public function __construct($groupIDs, $withPictures, $iExtraStudents, $iFYID, $startDate, $endDate)
    {
        parent::__construct();

        $this->groupIDs = $groupIDs;
        $this->withPictures = $withPictures;
        $this->iExtraStudents = $iExtraStudents;
        $this->iFYID = $iFYID;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function render()
    {
        // we will construct the labels
        $labelArr = [];
        $labelArr['firstName'] = "First Name";
        $labelArr['lastName'] = "Last Name";
        $labelArr['birthDate'] = "Birth Date";
        $labelArr['gender'] = "Gender";
        $labelArr['age'] = "Age";
        $labelArr['homePhone'] = "Phone";
        $labelArr['groupName'] = "Group";
        $labelArr['props'] = "Notes";
        $labelArr['stats'] = "Stats";

        $nbrGroup = count($this->groupIDs);

        foreach ($this->groupIDs as $iGroupID) {

            // we filter all the events which belongs to a group
            $activeEvents = EventQuery::Create()
                ->filterByGroupId($iGroupID)
                ->filterByInActive(1, Criteria::NOT_EQUAL)
                ->Where('event_start BETWEEN "' . $this->startDate . '" AND "' . $this->endDate . '"')// We filter only the events from the current month : date('Y')
                ->orderByStart()
                ->find();

            $date_count = 0;

            $labelArr = [];

            foreach ($activeEvents as $activeEvent) {// we loop in the events of the year
                $labelArr['date' . $date_count++] = OutputUtils::change_date_for_place_holder($activeEvent->getStart()->format("Y-m-d"));
            }


            // Instantiate the class and build the report.
            $yTitle = 20;
            $yTeachers = $yTitle + 6;
            $nameX = 10;
            $epd = 3;


            //  uset($aStudents);
            //Get the data on this group
            $group = GroupQuery::Create()->findOneById($iGroupID);

            if (!is_null($group)) {
                $reportHeader = str_pad($group->getName(), 95); //. $this->iFYID;
            }

            // Build the teacher string- first teachers, then the liaison
            $teacherString = _('Teachers') . ': ';
            $bFirstTeacher = true;
            $iTeacherCnt = 0;
            $iMaxTeachersFit = 4;
            $iStudentCnt = 0;

            $groupRoleMemberships = \EcclesiaCRM\Person2group2roleP2g2rQuery::create()
                ->joinWithPerson()
                ->orderBy(PersonTableMap::COL_PER_FIRSTNAME) // I've try to reproduce ORDER BY per_LastName, per_FirstName
                ->findByGroupId($iGroupID);

            $aStudents = [];

            $maxNbrEvents = 0;

            foreach ($groupRoleMemberships as $groupRoleMembership) {
                $lineArr = [];
                $lineRealPresence = 0;
                $lineNbrEvents = 0;
                $lineDates = [];

                $person = $groupRoleMembership->getPerson();

                $family = $person->getFamily();

                $homePhone = "";
                if (!empty($family)) {
                    $homePhone = $family->getHomePhone();

                    if (empty($homePhone)) {
                        $homePhone = $family->getCellPhone();
                    }

                    if (empty($homePhone)) {
                        $homePhone = $family->getWorkPhone();
                    }
                }

                $groupRole = ListOptionQuery::create()->filterById($group->getRoleListId())->filterByOptionId($groupRoleMembership->getRoleId())->findOne();
                $lst_OptionName = $groupRole->getOptionName();

                if ($lst_OptionName == 'Student') {// we will draw only the students
                    $assignedProperties = Record2propertyR2pQuery::Create()
                        ->findByR2pRecordId($person->getId());

                    $props = "";
                    foreach ($assignedProperties as $assproperty) {
                        $property = PropertyQuery::Create()->findOneByProId($assproperty->getR2pProId());
                        $props .= $property->getProName() . ", ";
                    }

                    $date_count = 0;

                    foreach ($activeEvents as $activeEvent) {// we loop in the events of the year
                        $eventAttendee = EventAttendQuery::create()
                            ->filterByPersonId($person->getId())
                            ->filterByEventId($activeEvent->getId())
                            ->findOne();

                        if (!is_null($eventAttendee) && !empty($eventAttendee->getCheckinDate())) {
                            $lineDates['date' . $date_count++] = 1;
                            $lineRealPresence++;
                        } else {
                            $lineDates['date' . $date_count++] = 0;
                        }

                        $lineNbrEvents++;
                    }

                    $lineArr['firstName'] = $person->getFirstName();
                    $lineArr['lastName'] = $person->getLastName();
                    $lineArr['birthDate'] = OutputUtils::FormatDate($person->getBirthDate()->format("Y-m-d"));
                    $lineArr['gender'] = ($person->getGender() == 1) ? _("Boy") : _("Girl");
                    $lineArr['age'] = $person->getAge(false);
                    $lineArr['homePhone'] = $homePhone;
                    $lineArr['groupName'] = $group->getName();
                    $lineArr['props'] = $props;
                    $lineArr['stats'] = $lineRealPresence;
                    $lineArr['photos'] = $person->getPhoto()->getThumbnailURI();

                    $lineArr = array_merge($lineArr, $lineDates);

                    $aStudents[] = $lineArr;

                    if ($maxNbrEvents < $lineNbrEvents) {
                        $maxNbrEvents = $lineNbrEvents;
                    }
                }
            }


            // we add now the free attendees
            $date_count = 0;
            $evenCountNames = [];
            $lineRealPresence = 0;

            foreach ($activeEvents as $activeEvent) {
                $eventCounts = EventCountsQuery::Create()
                    ->filterByEvtcntEventid($activeEvent->getId())
                    ->orderByEvtcntCountid(Criteria::ASC)
                    ->find();

                if ( $eventCounts->count() == 0) {
                    $eventCountNames = EventCountNameQuery::Create()
                        ->leftJoinEventTypes()
                        ->Where('type_id=' . $activeEvent->getType())
                        ->find();

                    foreach ($eventCountNames as $eventCountName) {
                        $eventCount = EventCountsQuery::Create()
                            ->filterByEvtcntEventid($activeEvent->getId())
                            ->findOneByEvtcntCountid($eventCountName->getId());

                        if (is_null($eventCount)) {
                            $eventCount = new EventCounts;
                            $eventCount->setEvtcntEventid($activeEvent->getId());
                            $eventCount->setEvtcntCountid($eventCountName->getId());
                            $eventCount->setEvtcntCountname($eventCountName->getName());
                            $eventCount->setEvtcntCountcount(0);
                            $eventCount->setEvtcntNotes("");
                            $eventCount->save();
                        }
                    }

                    $eventCounts = EventCountsQuery::Create()
                        ->filterByEvtcntEventid($activeEvent->getId())
                        ->orderByEvtcntCountid(Criteria::ASC)
                        ->find();
                }

                $lineDates['date' . $date_count] = 0;

                foreach ($eventCounts as $eventCount) {
                    if (!in_array($eventCount->getEvtcntCountname(),$evenCountNames)) {
                        $evenCountNames[] = $eventCount->getEvtcntCountname();
                    }
                    if ($eventCount->getEvtcntCountcount()) {
                        $lineDates['date' . $date_count] += $eventCount->getEvtcntCountcount();
                        $lineRealPresence += $eventCount->getEvtcntCountcount();
                    }
                }

                $date_count++;
            }

            $lineArr['firstName'] = implode(", ",$evenCountNames);
            $lineArr['lastName'] = _("None");
            $lineArr['birthDate'] = "";
            $lineArr['gender'] = "?";
            $lineArr['age'] = "?";
            $lineArr['homePhone'] = "";
            $lineArr['groupName'] = "";
            $lineArr['props'] = "";
            $lineArr['stats'] = $lineRealPresence;
            $lineArr['photos'] = SessionUser::getUser()->getPerson()->getPhoto()->getThumbnailURI();

            $lineArr = array_merge($lineArr, $lineDates);

            $aStudents[] = $lineArr;

            // now we finish the work

            $y = 0;

            $y = $this->DrawRealAttendanceCalendar($nameX, $y + 6, $labelArr, $aStudents, _('Students'), $this->iExtraStudents,
                $this->startDate, $this->endDate, $reportHeader, $this->withPictures, $maxNbrEvents);

            $nbrGroup--;

            if ($nbrGroup > 0) {
                $this->AddPage();
            }
        }

        header('Pragma: public');  // Needed for IE when using a shared SSL certificate
        ob_end_clean();
        if (SystemConfig::getValue('iPDFOutputType') == 1) {
            $this->Output('ClassAttendance' . date(SystemConfig::getValue("sDateFilenameFormat")) . '.pdf', 'D');
        } else {
            $this->Output();
        }

    }
}
