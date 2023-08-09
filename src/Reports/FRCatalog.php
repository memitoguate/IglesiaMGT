<?php
/*******************************************************************************
*
*  filename    : Reports/ConfirmReport.php
*  last change : 2003-08-30
*  description : Creates a PDF with all the confirmation letters asking member
*                families to verify the information in the database.

******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\OutputUtils;

use EcclesiaCRM\FundRaiserQuery;
use EcclesiaCRM\DonatedItemQuery;

use EcclesiaCRM\Map\PersonTableMap;
use EcclesiaCRM\Map\DonatedItemTableMap;

use EcclesiaCRM\Reports\PDF_FRCatalogReport;

use Propel\Runtime\ActiveQuery\Criteria;

$iCurrentFundraiser = $_GET['CurrentFundraiser'];

$curY = 0;

// Get the information about this fundraiser
$thisFRORM = FundRaiserQuery::create()->findOneById($iCurrentFundraiser);

$fundTitle = $thisFRORM->getTitle();
$fundDescription = $thisFRORM->getDescription();

$currency = SystemConfig::getValue("sCurrency");

// Get all the donated items
$ormItems = DonatedItemQuery::create()
    ->addJoin(DonatedItemTableMap::COL_DI_DONOR_ID, PersonTableMap::COL_PER_ID, Criteria::LEFT_JOIN)
    ->addAsColumn('FirstName', PersonTableMap::COL_PER_FIRSTNAME)
    ->addAsColumn('LastName', PersonTableMap::COL_PER_LASTNAME)
    ->addAsColumn('cri1', 'SUBSTR('. DonatedItemTableMap::COL_DI_ITEM.',1,1)')
    ->addAsColumn('cri2', 'cast(SUBSTR('. DonatedItemTableMap::COL_DI_ITEM.',2) as unsigned integer)')
    ->addAsColumn('cri3', 'SUBSTR('. DonatedItemTableMap::COL_DI_ITEM.',4)')
    ->orderBy('cri1')
    ->orderBy('cri2')
    ->orderBy('cri3')
    ->findByFrId($iCurrentFundraiser);

$pdf = new PDF_FRCatalogReport($fundTitle, $fundDescription);
$pdf->SetTitle($fundTitle);

// Loop through items
$idFirstChar = '';

foreach ($ormItems as $item) {
    $newIdFirstChar = mb_substr($item->getItem(), 0, 1);
    $maxYNewPage = 220;
    if ($item->getPicture() != '') {
        $maxYNewPage = 120;
    }
    if ($pdf->GetY() > $maxYNewPage || ($idFirstChar != '' && $idFirstChar != $newIdFirstChar)) {
        $pdf->AddPage();
    }
    $idFirstChar = $newIdFirstChar;

    $pdf->SetFont('Times', 'B', 12);
    $pdf->Write(6, $item->getItem().': ');
    $pdf->Write(6, stripslashes($item->getTitle())."\n");

    if ($item->getPicture() != '' && strlen($item->getPicture()) > 5) {
        $s = getimagesize($item->getPicture());
        if ($s[0]>0) {
            $h = (100.0 / $s[0]) * $s[1];
            $pdf->Image($item->getPicture(), $pdf->GetX(), $pdf->GetY(), 100.0, $h);
            $pdf->SetY($pdf->GetY() + $h);
        }
    }

    $pdf->SetFont('Times', '', 12);
    $pdf->Write(6, stripslashes($item->getDescription())."\n");
    if ($item->getMinimum() > 0) {
        $pdf->Write(6, _('Minimum bid ').$currency.OutputUtils::money_localized($item->getMinimum()).'.  ');
    }
    if ($item->getEstPrice() > 0) {
        $pdf->Write(6, _('Estimated value ').$currency.OutputUtils::money_localized($item->getEstPrice()).'.  ');
    }
    if ($item->getLastName() != '') {
        $pdf->Write(6, _('Donated by ').$item->getFirstName().' '.$item->getLastName().".\n");
    }
    $pdf->Write(6, "\n");
}

header('Pragma: public');  // Needed for IE when using a shared SSL certificate
ob_end_clean();
if (SystemConfig::getValue('iPDFOutputType') == 1) {
    $pdf->Output('FRCatalog'.date(SystemConfig::getValue("sDateFilenameFormat")).'.pdf', 'D');
} else {
    $pdf->Output();
}
