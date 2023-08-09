<?php
/*******************************************************************************
 *
 *  filename    : financialReports.php
 *  last change : 2023-06-06
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2023 EcclesiaCRM
 *
 ******************************************************************************/

 use EcclesiaCRM\Utils\MiscUtils;
 use EcclesiaCRM\dto\SystemConfig;
 use EcclesiaCRM\SessionUser;
 use EcclesiaCRM\ListOptionQuery;
 use EcclesiaCRM\DepositQuery;
 use EcclesiaCRM\FamilyQuery;
 use EcclesiaCRM\DonationFundQuery;
 
 use Propel\Runtime\Propel;
 use Propel\Runtime\ActiveQuery\Criteria;

// we place this part to avoid a problem during the upgrade process
// Set the page title
require $sRootDocument . '/Include/Header.php';
?>

<div class="card card-body">
<br>
<?php

// No Records Message if previous report returned no records.
if ($ReturnMessage == 'NoRows') {
?>
    <h3 style="color:red"><?= _("No records were returned from the previous report.")?></h3>
<?php
}

if ($sReportType == '') {
    // First Pass - Choose report type
?>
<form method=post action='<?= $sRootPath?>/v2/deposit/financial/reports'>
  <table cellpadding=3>
    <tr>
      <td class=LabelColumn><?= _("Report Type:") ?>&nbsp;&nbsp;</td>
      <td class=TextColumn>
        <select name=ReportType class="form-control form-control-sm">
          <option value=0><?= _("Select Report Type") ?></option>
          <option value='Pledge Summary'><?= _("Pledge Summary") ?></option>
          <option value='Pledge Family Summary'><?= _("Pledge Family Summary") ?></option>
          <option value='Pledge Reminders'><?= _("Pledge Reminders") ?></option>
          <option value='Voting Members'><?= _("Voting Members") ?></option>
          <option value='Giving Report'><?= _("Giving Report (Tax Statements)") ?></option>
          <option value='Zero Givers'><?= _("Zero Givers") ?></option>
          <option value='Individual Deposit Report'><?= _("Individual Deposit Report") ?></option>
          <option value='Advanced Deposit Report'><?= _("Advanced Deposit Report") ?></option>
        </select>
      </td>
    </tr>
<?php
    // First Pass Cancel, Next Buttons
?>
    <tr>
      <td>&nbsp;</td>
      <td><br><input type=button class='btn btn-default' name=Cancel value='<?= _("Cancel")?>'
        onclick="javascript:document.location='<?= $sRootPath ?>/v2/system/report/list';">
        <input type=submit class='btn btn-primary' name=Submit1 value='<?= _("Next") ?>'>
      </td>
    </tr>
  </table>
</form>
<?php
} else {
    $iFYID = $_SESSION['idefaultFY'];
    if ($year != -1) {
      $iCalYear = $year;
    } else {
      $iCalYear = date('Y');
    }
    // 2nd Pass - Display filters and other settings
    // Set report destination, based on report type
    switch ($sReportType) {
        case 'Giving Report':
            $action = $sRootPath.'/Reports/TaxReport.php';
        break;
        case 'Zero Givers':
            $action = $sRootPath.'/Reports/ZeroGivers.php';
        break;
        case 'Pledge Summary':
            $action = $sRootPath.'/Reports/PledgeSummary.php';
        break;
        case 'Pledge Family Summary':
            $action = $sRootPath.'/Reports/FamilyPledgeSummary.php';
        break;
        case 'Pledge Reminders':
            $action = $sRootPath.'/Reports/ReminderReport.php';
        break;
        case 'Voting Members':
            $action = $sRootPath.'/Reports/VotingMembers.php';
        break;
        case 'Individual Deposit Report':
            $action = $sRootPath.'/Reports/PrintDeposit.php';
        break;
        case 'Advanced Deposit Report':
            $action = $sRootPath.'/Reports/AdvancedDeposit.php';
        break;
    }
?>
<form method=post action="<?= $action ?>">
  <input type=hidden name=ReportType value='<?= $sReportType?>'>
  <table cellpadding=3>
    <tr>
      <td>
        <h3><?= _("Filters")?></h3>
      </td>
    </tr>
<?php
    // Filter by Classification and Families
    if ($sReportType == 'Giving Report' || $sReportType == 'Pledge Reminders' || $sReportType == 'Pledge Family Summary' || $sReportType == 'Advanced Deposit Report') {

        //Get Classifications for the drop-down
        $ormClassifications = ListOptionQuery::Create()
              ->orderByOptionSequence()
              ->findById(1);
         ?>



    <tr>
      <td class="LabelColumn"><?= _("Classification") ?>:<br></td>
      <td class=TextColumnWithBottomBorder>
          <div class=SmallText></div>
          <select name="classList[]" style="width:100%" multiple id="classList">
        <?php
          foreach ($ormClassifications as $ormClassification) {
        ?>
            <option value="<?= $ormClassification->getOptionID() ?>"><?= $ormClassification->getOptionName() ?>&nbsp;
        <?php
          }
        ?>
          </select>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <br/>
        <button type="button" id="addAllClasses" class="btn btn-success"><?= _("Add All Classes") ?></button>
        <button type="button" id="clearAllClasses" class="btn btn-danger"><?= _("Clear All Classes") ?></button><br/><br/>
      </td>
    </tr>
        <?php
          $families = FamilyQuery::Create()->orderByName()->find();
        ?>
    <tr>
      <td class=LabelColumn><?= _("Filter by Family") ?>:<br></td>
      <td class=TextColumnWithBottomBorder>
        <select name="family[]" id="family" multiple style="width:100%">
        <?php
        // Build Criteria for Head of Household
        if (!$sDirRoleHead) {
            $sDirRoleHead = '1';
        }
        $head_criteria = ' per_fmr_ID = '.$sDirRoleHead;
        // If more than one role assigned to Head of Household, add OR
        $head_criteria = str_replace(',', ' OR per_fmr_ID = ', $head_criteria);
        // Add Spouse to criteria
        if (intval($sDirRoleSpouse) > 0) {
            $head_criteria .= " OR per_fmr_ID = $sDirRoleSpouse";
        }
        // Build array of Head of Households and Spouses with fam_ID as the key
        $sSQL = 'SELECT per_FirstName, per_fam_ID FROM person_per WHERE per_fam_ID > 0 AND ('.$head_criteria.') ORDER BY per_fam_ID';

        $connection = Propel::getConnection();

        $statement = $connection->prepare($sSQL);
        $statement->execute();


        $aHead = [];
        while ($row = $statement->fetch( \PDO::FETCH_BOTH )) {
            if ($row['per_FirstName'] && array_key_exists($row['per_fam_ID'], $aHead)) {
                $aHead[$row['per_fam_ID']] .= ' & '.$row['per_FirstName'];
            } elseif ($row['per_FirstName']) {
                $aHead[$row['per_fam_ID']] = $row['per_FirstName'];
            }

        }

        foreach ($families as $family) {
      ?>
            <option value=<?= $family->getId() ?>><?= $family->getName() ?>
            <?php
            if (array_key_exists($family->getId(), $aHead)) {
            ?>
                , <?= $aHead[$family->getId()]?>
            <?php
            }
            ?>
            <?= MiscUtils::FormatAddressLine($family->getAddress1(), $family->getCity(), $family->getState()) ?>
        <?php
        }
        ?>
        </select>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <br/>
        <button type="button" id="addAllFamilies" class="btn btn-success"><?= _("Add All Families") ?></button>
        <button type="button" id="clearAllFamilies" class="btn btn-danger"><?= _("Clear All Families") ?></button><br/><br/>
      </td>
    </tr>
  <?php
    }

    // Starting and Ending Dates for Report
    if ($sReportType == 'Giving Report' || $sReportType == 'Advanced Deposit Report' || $sReportType == 'Zero Givers') {
      if ($iCalYear == -1) {
        $date = new \DateTime('now');
        $today = $date->format(SystemConfig::getValue('sDateFormatLong'));
      } else {
        $date = new \DateTime($iCalYear."-01-02");
        $today = $date->format(SystemConfig::getValue('sDateFormatLong'));
      }

      $one_year_after  = ($date->add(new \DateInterval('P366D')))->format(SystemConfig::getValue('sDateFormatLong'));
  ?>
    <tr>
      <td class=LabelColumn><?= _("Report Start Date:")?></td>
      <td class=TextColumn>
        <input type=text name=DateStart class='date-picker form-control' maxlength=10 id=DateStart size=11 value='<?= $today ?>'>
      </td>
    </tr>
    <tr>
      <td class=LabelColumn><?= _("Report End Date:") ?></td>
      <td class=TextColumn>
        <input type=text name=DateEnd class='date-picker form-control' maxlength=10 id=DateEnd size=11 value='<?= $one_year_after?>'>
      </td>
    </tr>

  <?php
  if ($sReportType == 'Giving Report' || $sReportType == 'Advanced Deposit Report') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Apply Report Dates To:") ?></td>
      <td class=TextColumnWithBottomBorder>
        <input name=datetype type=radio checked value='Deposit'>
         <?= _("Deposit Date (Default)") ?>
       &nbsp;
        <input name=datetype type=radio value='Payment'>
          <?= _("Payment Date")?>
    </tr>
  <?php
        }
    }

    // Fiscal Year
    if ($sReportType == 'Pledge Summary' || $sReportType == 'Pledge Reminders' || $sReportType == 'Pledge Family Summary' || $sReportType == 'Voting Members') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Fiscal Year:") ?></td>
      <td class=TextColumn>
        <?= MiscUtils::PrintFYIDSelect($iFYID, 'FYID')?>
      </td>
    </tr>
    <?php
    }

  // Filter by Deposit
  if ($sReportType == 'Giving Report' || $sReportType == 'Individual Deposit Report' || $sReportType == 'Advanced Deposit Report') {
      $deposits = DepositQuery::Create()->orderById(Criteria::DESC)->limit(200)->find();
  ?>
    <tr>
      <td class=LabelColumn><?= _("Filter by Deposit:")?><br></td>
      <td class=TextColumnWithBottomBorder>
        <div class=SmallText>
          <?php
          if ($sReportType != 'Individual Deposit Report') {
          ?>
              <?= _("If deposit is selected, date criteria will be ignored.") ?>
          <?php
          }
          ?>
        </div>
        <select name="deposit" class= "form-control form-control-sm">
        <?php
        if ($sReportType != 'Individual Deposit Report') {
        ?>
            <option value=0 selected><?= _("All deposits within date range")?></option>
        <?php
        }
        foreach ($deposits as $deposit) {
        ?>
          <option value=<?= $deposit->getId() ?>">#<?= $deposit->getId() ?> &nbsp; (<?= date(SystemConfig::getValue('sDateFormatLong'), strtotime($deposit->getDate()->format('Y-m-d'))) ?>) &nbsp;-&nbsp;<?= _($deposit->getType()) ?>
        <?php
        }
        ?>
        </select>
      </td>
    </tr>
  <?php
    }

    // Filter by Account
    if ($sReportType == 'Pledge Summary' || $sReportType == 'Pledge Family Summary' || $sReportType == 'Giving Report' || $sReportType == 'Advanced Deposit Report' || $sReportType == 'Pledge Reminders') {
        $funds = DonationFundQuery::Create()->orderByActive()->orderByName()->find();
   ?>
    <tr>
       <td class="LabelColumn"><?= _("Filter by Fund") ?>:<br></td>
       <td>
         <select name="funds[]" multiple id="fundsList" style="width:100%">
    <?php
      foreach ($funds as $fund) {
    ?>
           <option value=<?= $fund->getId()?>><?= $fund->getName().(($fund->getActive() == 'false')?' &nbsp; '._("INACTIVE"):"")?>
    <?php
      }
    ?>
         </select>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <br/>
        <button type="button" id="addAllFunds" class="btn btn-success"><?= _("Add All Funds") ?></button>
        <button type="button" id="clearAllFunds" class="btn btn-danger"><?= _("Clear All Funds") ?></button>
        <br/><br/>
      </td>
    </tr>
  <?php
    }
    // Filter by Payment Method
    if ($sReportType == 'Advanced Deposit Report') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Filter by Payment Type:")?><br></td>
      <td class=TextColumnWithBottomBorder>
        <div class=SmallText>
          <?= _("Use Ctrl Key to select multiple")?>
        </div>
        <select name=method[] size=5 multiple>
          <option value=0 selected><?= _("All Methods")?>
          <option value='CHECK'><?=_("Check")?>
          <option value='CASH'><?= _("Cash")?>
          <option value='CREDITCARD'><?= _("Credit Card") ?>
          <option value='BANKDRAFT'><?= _("Bank Draft")?>
          <option value='EGIVE'><?= _("eGive")?>
        </select>
      </td>
    </tr>
  <?php
    }

    if ($sReportType == 'Giving Report') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Minimun Total Amount:") ?></td>
      <td class=TextColumnWithBottomBorder>
         <div class=SmallText>
           (<?= _("0 - No Minimum") ?>)
         </div>
         <input name=minimum type=text value='0' size=8 class= "form-control form-control-sm">
      </td>
    </tr>
  <?php
    }
    // Other Settings
  ?>
    <tr>
      <td>
        <h3><?= _("Other Settings") ?></h3>
      </td>
    </tr>
  <?php
    if ($sReportType == 'Pledge Reminders') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Include:") ?></td>
      <td class=TextColumnWithBottomBorder>
         <input name=pledge_filter type=radio value='pledge' checked><?= _("Only Payments with Pledges") ?>
          &nbsp; <input name=pledge_filter type=radio value='all'><?= _("All Payments") ?>
      </td>
    </tr>
    <tr>
      <td class=LabelColumn><?= _("Generate:") ?></td>
      <td class=TextColumnWithBottomBorder>
         <input name=only_owe type=radio value='yes' checked><?= _("Only Families with unpaid pledges") ?>
         &nbsp; <input name=only_owe type=radio value='no'><?= _("All Families") ?>
      </td>
    </tr>
  <?php
    }

    if ($sReportType == 'Giving Report' || $sReportType == 'Zero Givers') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Report Heading:") ?></td>
      <td class=TextColumnWithBottomBorder>
         <input name=letterhead type=radio value='graphic'><?= _("Graphic")?>
         <input name=letterhead type=radio value='address' checked><?= _("Church Address") ?>
         <input name=letterhead type=radio value='none'><?= _("Blank") ?>
      </td>
    </tr>
    <tr>
      <td class=LabelColumn><?= _("Remittance Slip:") ?></td>
      <td class=TextColumnWithBottomBorder>
        <input name=remittance type=radio value='yes'><?= _("Yes") ?>
        <input name=remittance type=radio value='no' checked><?= _("No")?>
      </td>
    </tr>
  <?php
    }

    if ($sReportType == 'Advanced Deposit Report') {
  ?>
    <tr>
      <td class=LabelColumn><?= _("Sort Data by:")?></td>
      <td class=TextColumnWithBottomBorder>
        <input name=sort type=radio value='deposit' checked><?= _("Deposit") ?>
          &nbsp;<input name=sort type=radio value='fund'><?= _("Fund") ?>
          &nbsp;<input name=sort type=radio value='family'><?= _("Family")?>
      </td>
    </tr>
    <tr>
      <td class=LabelColumn><?= _("Report Type:") ?></td>
      <td class=TextColumnWithBottomBorder>
        <input name=detail_level type=radio value='detail' checked><?= _("All Data") ?>
        <input name=detail_level type=radio value='medium'><?= _("Moderate Detail") ?>
        <input name=detail_level type=radio value='summary'><?= _("Summary Data") ?>
      </td>
    </tr>
  <?php
    }

    if ($sReportType == 'Voting Members') {
  ?>
    <tr>
       <td class=LabelColumn><?= _("Voting members must have made<br> a donation within this many years<br> (0 to not require a donation):")?></td>
       <td class=TextColumnWithBottomBorder><input name=RequireDonationYears type=text value=0 size=5 class= "form-control form-control-sm"></td>
    </tr>
  <?php
    }

    if (((SessionUser::getUser()->isAdmin() && $_SESSION['bCSVAdminOnly'] ) || !$_SESSION['bCSVAdminOnly'] )
        &&
        ($sReportType == 'Pledge Summary'
          || $sReportType == 'Giving Report'
          || $sReportType == 'Individual Deposit Report'
          || $sReportType == 'Advanced Deposit Report'
          || $sReportType == 'Zero Givers'
        )) {
  ?>
    <tr>
      <td class=LabelColumn><?= _('Output Method:') ?></td>
      <td class=TextColumnWithBottomBorder>
        <input name=output type=radio checked value='pdf'>PDF
        <input name=output type=radio value='csv'>CSV
      </td>
    </tr>
  <?php
    } else {
  ?>
    <tr><td><input name=output type=hidden value='pdf'></td></tr>
  <?php
    }
    // Back, Next Buttons
  ?>
    <tr>
      <td>&nbsp;</td>
      <td><input type=button class='btn btn-default' name=Cancel value='<?= _("Back") ?>'
        onclick="javascript:document.location='<?= $sRootPath?>/v2/deposit/financial/reports';">
        <input type=submit class='btn btn-primary' name=Submit2 value='<?= _("Create Report") ?>'>
      </td>
    </tr>
  </table>
</form>

<?php
}
?>
<br/>
</div>

<?php require $sRootDocument . '/Include/Footer.php'; ?>

<script src="<?= $sRootPath ?>/skin/js/finance/FinancialReports.js"></script>
