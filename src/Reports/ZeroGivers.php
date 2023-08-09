<?php
/*******************************************************************************
*
*  filename    : Reports/ZeroGivers.php
*  last change : 2021-11-02
*  description : Creates a PDF with all the tax letters for a particular calendar year.
*  Copyright 2012-2021 Michael Wilt & Philippe Logel

******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\Reports\PDF_ZeroGivers;

use Propel\Runtime\Propel;

// Security
if ( !( SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance') ) ) {
    RedirectUtils::Redirect('v2/dashboard');
    exit;
}

$delimiter = SessionUser::getUser()->CSVExportDelemiter();
$charset   = SessionUser::getUser()->CSVExportCharset();

// Filter values
$output = InputUtils::LegacyFilterInput($_POST['output']);
$sDateStart = InputUtils::FilterDate($_POST['DateStart'], 'date');
$sDateEnd = InputUtils::FilterDate($_POST['DateEnd'], 'date');

$letterhead = InputUtils::LegacyFilterInput($_POST['letterhead']);
$remittance = InputUtils::LegacyFilterInput($_POST['remittance']);

// If CSVAdminOnly option is enabled and user is not admin, redirect to the menu.
if (!SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getValue('bCSVAdminOnly') && $output != 'pdf') {
    RedirectUtils::Redirect('v2/dashboard');
    exit;
}

$today = date('Y-m-d');
if (!$sDateEnd && $sDateStart) {
    $sDateEnd = $sDateStart;
}
if (!$sDateStart && $sDateEnd) {
    $sDateStart = $sDateEnd;
}
if (!$sDateStart && !$sDateEnd) {
    $sDateStart = $today;
    $sDateEnd = $today;
}
if ($sDateStart > $sDateEnd) {
    $temp = $sDateStart;
    $sDateStart = $sDateEnd;
    $sDateEnd = $temp;
}

// Build SQL Query
// Build SELECT SQL Portion
$sSQL = "SELECT DISTINCT fam_ID, fam_Name, fam_Address1, fam_Address2, fam_City, fam_State, fam_Zip, fam_Country
       FROM family_fam LEFT OUTER JOIN person_per ON fam_ID = per_fam_ID
       WHERE per_cls_ID=1 AND fam_ID NOT IN
       (SELECT DISTINCT plg_FamID FROM pledge_plg WHERE plg_date BETWEEN '$sDateStart' AND '$sDateEnd' AND plg_PledgeOrPayment = 'Payment')
       ORDER BY fam_ID";

//Execute SQL Statement
$connection = Propel::getConnection();
$rsReport = $connection->prepare($sSQL);
$rsReport->execute();


// Exit if no rows returned
$iCountRows = $rsReport->rowCount();
if ($iCountRows < 1) {
    RedirectUtils::Redirect('v2/deposit/financial/reports/NoRows/Zero%20Givers');
}

// Create Giving Report -- PDF
// ***************************

if ($output == 'pdf') {

    // Set up bottom border values
    if ($remittance == 'yes') {
        $bottom_border1 = 134;
        $bottom_border2 = 180;
    } else {
        $bottom_border1 = 200;
        $bottom_border2 = 250;
    }

    // Instantiate the directory class and build the report.
    $pdf = new PDF_ZeroGivers($letterhead, $sDateStart, $sDateEnd, $remittance);

    // Loop through result array
    while ($row = $rsReport->fetch( \PDO::FETCH_ASSOC )) {
        extract($row);
        $curY = $pdf->StartNewPage($fam_ID, $fam_Name, $fam_Address1, $fam_Address2, $fam_City, $fam_State, $fam_Zip, $fam_Country);

        $pdf->FinishPage($curY, $fam_ID, $fam_Name, $fam_Address1, $fam_Address2, $fam_City, $fam_State, $fam_Zip, $fam_Country);
    }

    ob_end_clean();
    if (SystemConfig::getValue('iPDFOutputType') == 1) {
        $pdf->Output('ZeroGivers'.date(SystemConfig::getValue("sDateFilenameFormat")).'.pdf', 'D');
    } else {
        $pdf->Output();
    }

    // Output a text file
// ##################
} elseif ($output == 'csv') {

    // Settings
    //$delimiter = ',';
    $delimiter = $delimiter;
    $eol = "\r\n";

    // Build headings row
    preg_match('SELECT (.*) FROM ', $sSQL, $result);
    $headings = explode(',', $result[1]);
    $buffer = '';
    foreach ($headings as $heading) {
        $buffer .= trim($heading).$delimiter;
    }
    // Remove trailing delimiter and add eol
    $buffer = mb_substr($buffer, 0, -1).$eol;

    // Add data
    while ($row = $rsReport->fetch( \PDO::FETCH_ASSOC )) {
        foreach ($row as $field) {
            $field = str_replace($delimiter, ' ', $field);    // Remove any delimiters from data
            $buffer .= InputUtils::translate_special_charset($field).$delimiter;
        }
        // Remove trailing delimiter and add eol
        $buffer = mb_substr($buffer, 0, -1).$eol;
    }

    // Export file
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Description: File Transfer');
    header('Content-Type: text/csv;charset='.$charset);
    header('Content-Disposition: attachment; filename=EcclesiaCRM-'.date(SystemConfig::getValue("sDateFilenameFormat")).'.csv');
    header('Content-Transfer-Encoding: binary');

    if ($charset == "UTF-8") {
       echo "\xEF\xBB\xBF";
    }

    echo $buffer;
}
