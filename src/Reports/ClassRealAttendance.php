<?php
/*******************************************************************************
*
*  filename    : Reports/ClassRealAttendance.php
*  description : Creates a PDF for a Sunday School Class Attendance List
*  copyright   : 2018 Philippe Logel all right reserved not MIT licence
*                This code can't be incoprorated in another software without any authorizaion
******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';


use EcclesiaCRM\Reports\PDF_RealAttendance;
use EcclesiaCRM\Reports\CSV_RealAttendance;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;

//Security
if (is_null(SessionUser::getUser())) {
    RedirectUtils::Redirect('v2/dashboard');
    exit;
}

// we get all the params
$iGroupID = InputUtils::LegacyFilterInput($_GET['groupID']);

$groups = explode(',', $iGroupID);

$withPictures = InputUtils::LegacyFilterInput($_GET['withPictures'], 'int');
$iExtraStudents = InputUtils::LegacyFilterInputArr($_GET, 'ExtraStudents', 'int');

$iFYID = $_SESSION['idefaultFY'];// $iFYID = InputUtils::LegacyFilterInput($_GET['FYID'], 'int'); //


$startDate = $_GET['start'];
$endDate   = $_GET['end'];

$exportTypePDF = $_GET['exportTypePDF'];

if ($exportTypePDF) {
    $pdfRealAttendees = new PDF_RealAttendance($groups, $withPictures, $iExtraStudents, $iFYID, $startDate, $endDate);
    $pdfRealAttendees->render();
} else {
    $csvRealAttendees = new CSV_RealAttendance($groups,$withPictures,$iExtraStudents,$iFYID,$startDate,$endDate);
    $csvRealAttendees->render();
}

