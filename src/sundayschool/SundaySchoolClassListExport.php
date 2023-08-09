<?php
/*******************************************************************************
*
*  filename    : sundayschol/SundaySchoolClassListExport.php
*  last change : 2017-11-03 Philippe Logel
*  description : Creates a csv for a Sunday School Class List
*
******************************************************************************/
require '../Include/Config.php';
require '../Include/Functions.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\GroupQuery;
use EcclesiaCRM\Record2propertyR2pQuery;
use EcclesiaCRM\PropertyQuery;
use EcclesiaCRM\Map\PersonTableMap;
use EcclesiaCRM\Utils\RedirectUtils;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\SessionUser;

if ( !( SessionUser::getUser()->isCSVExportEnabled() ) ) {
    RedirectUtils::Redirect('v2/dashboard');
    exit;
}

$delimiter = SessionUser::getUser()->CSVExportDelemiter();
$charset   = SessionUser::getUser()->CSVExportCharset();

header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Content-Description: File Transfer');
header('Content-Type: text/csv;charset='.$charset);
header('Content-Disposition: attachment; filename=SundaySchool-'.date(SystemConfig::getValue("sDateFilenameFormat")).'.csv');
header('Content-Transfer-Encoding: binary');


$out = fopen('php://output', 'w');

//add BOM to fix UTF-8 in Excel 2016 but not under, so the problem is solved with the charset variable
if ($charset == "UTF-8") {
    fputs($out, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF)));
}


fputcsv($out, [InputUtils::translate_special_charset("Class",$charset),
  InputUtils::translate_special_charset(_("Role"),$charset),
  InputUtils::translate_special_charset(_("First Name"),$charset),
  InputUtils::translate_special_charset(_("Last Name"),$charset),
  InputUtils::translate_special_charset(_("Birth Date"),$charset),
  InputUtils::translate_special_charset(_("Mobile"),$charset),
  InputUtils::translate_special_charset(_("Home Phone"),$charset),
  InputUtils::translate_special_charset(_("Home Address"),$charset),
  InputUtils::translate_special_charset(_("Dad Name"),$charset),
  InputUtils::translate_special_charset(_("Dad Mobile"),$charset) ,
  InputUtils::translate_special_charset(_("Dad Email"),$charset),
  InputUtils::translate_special_charset(_("Mom Name"),$charset),
  InputUtils::translate_special_charset(_("Mom Mobile"),$charset),
  InputUtils::translate_special_charset(_("Mom Email"),$charset),
  InputUtils::translate_special_charset(_("Properties"),$charset) ], $delimiter);

// only the unday groups
$groups = GroupQuery::create()
                    ->orderByName(Criteria::ASC)
                    ->filterByType(4)
                    ->find();


foreach ($groups as $group) {
    $iGroupID = $group->getID();
    $sundayschoolClass = $group->getName();


    $groupRoleMemberships = EcclesiaCRM\Person2group2roleP2g2rQuery::create()
                            ->joinWithPerson()
                            ->usePersonQuery()
                              ->filterByDateDeactivated(null)// GDRP, when a person is completely deactivated
                            ->endUse()
                            ->orderBy(PersonTableMap::COL_PER_LASTNAME)
                            ->_and()->orderBy(PersonTableMap::COL_PER_FIRSTNAME) // I've try to reproduce per_LastName, per_FirstName
                            ->findByGroupId($iGroupID);

    foreach ($groupRoleMemberships as $groupRoleMembership) {
        $groupRole = EcclesiaCRM\ListOptionQuery::create()->filterById($group->getRoleListId())->filterByOptionId($groupRoleMembership->getRoleId())->findOne();

        $lst_OptionName = $groupRole->getOptionName();
        $member = $groupRoleMembership->getPerson();

        $firstName = $member->getFirstName();
        $middlename = $member->getMiddleName();
        $lastname = $member->getLastName();
        $birthDay = $member->getBirthDay();
        $birthMonth = $member->getBirthMonth();
        $birthYear = $member->getBirthYear();
        $homePhone = $member->getHomePhone();
        $mobilePhone = $member->getCellPhone();
        $hideAge = $member->hideAge();

        $family = $member->getFamily();

        $Address1 = $Address2 = $city = $state = $zip = " ";
        $dadFirstName = $dadLastName = $dadCellPhone = $dadEmail = " ";
        $momFirstName = $momLastName = $momCellPhone = $momEmail = " ";

        if (!empty($family)) {
            $famID = $family->getID();
            $Address1 = $family->getAddress1();
            $Address2 = $family->getAddress2();
            $city = $family->getCity();
            $state = $family->getState();
            $zip = $family->getZip();


            if ($lst_OptionName == "Student") {
                // only for a student
                $FAmembers = FamilyQuery::create()->findOneByID($famID)->getAdults();

                // il faut encore chercher les membres de la famille
                foreach ($FAmembers as $maf) {
                    if ($maf->getGender() == 1) {
                        // Dad
                        $dadFirstName = $maf->getFirstName();
                        $dadLastName = $maf->getLastName();
                        $dadCellPhone = $maf->getCellPhone();
                        $dadEmail = $maf->getEmail();
                    } elseif ($maf->getGender() == 2) {
                        // Mom
                        $momFirstName = $maf->getFirstName();
                        $momLastName = $maf->getLastName();
                        $momCellPhone = $maf->getCellPhone();
                        $momEmail = $maf->getEmail();
                    }
                }
            }
        }

        $assignedProperties = Record2propertyR2pQuery::Create()
                            ->findByR2pRecordId($member->getId());

        $props = " ";
        if ($lst_OptionName == "Student" && !empty($assignedProperties)) {
            foreach ($assignedProperties as $assproperty) {
                $property = PropertyQuery::Create()->findOneByProId ($assproperty->getR2pProId());
                $props.= $property->getProName().", ";
            }

            $props = chop($props, ", ");
        }

        $birthDate = '';
        if ($birthYear != '' && !$birthDate && (!$member->getFlags() || $lst_OptionName == "Student")) {
            $publishDate = DateTime::createFromFormat('Y-m-d', $birthYear.'-'.$birthMonth.'-'.$birthDay);
            $birthDate = $publishDate->format(SystemConfig::getValue("sDateFormatLong"));
        }

        fputcsv($out, [
            InputUtils::translate_special_charset($sundayschoolClass,$charset),
            InputUtils::translate_special_charset($lst_OptionName,$charset),
            InputUtils::translate_special_charset($firstName,$charset),
            InputUtils::translate_special_charset($lastname,$charset),
            $birthDate, $mobilePhone, $homePhone,
            InputUtils::translate_special_charset($Address1,$charset).' '.InputUtils::translate_special_charset($Address2,$charset).' '.InputUtils::translate_special_charset($city,$charset).' '.InputUtils::translate_special_charset($state,$charset).' '.$zip,
            InputUtils::translate_special_charset($dadFirstName,$charset).' '.InputUtils::translate_special_charset($dadLastName,$charset), $dadCellPhone, $dadEmail,
            InputUtils::translate_special_charset($momFirstName,$charset).' '.InputUtils::translate_special_charset($momLastName,$charset), $momCellPhone, $momEmail, $props], $delimiter);
    }
}

fclose($out);
