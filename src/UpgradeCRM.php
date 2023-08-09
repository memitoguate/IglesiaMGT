<?php

// Include the function library
require 'Include/Config.php';
$bSuppressSessionTests = true;
require 'Include/Functions.php';
require_once 'Include/Header-function.php';

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Service\SystemService;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;

// Set the page title and include HTML header
$sPageTitle = gettext('Upgrade EcclesiaCRM');

if (!SessionUser::getUser()->isAdmin()) {
    RedirectUtils::Redirect('index.php');
    exit;
}

require 'Include/HeaderNotLoggedIn.php';
Header_modals();
Header_body_scripts();

?>
<div class="col-lg-8 col-lg-offset-2" style="margin-top: 10px">
  <div class="timeline">
    <div class="time-label">
        <span class="bg-red">
            <?= gettext('Upgrade EcclesiaCRM').SystemService::getDBMainVersion() ?>
        </span>
    </div>
    <div>
      <i class="fas fa-database bg-blue"></i>
      <div class="timeline-item" >
        <h3 class="timeline-header"><?= gettext('Step 1: Backup Database') ?> <span id="status1"></span></h3>
        <div class="timeline-body" id="backupPhase">
          <p><?= gettext('Please create a database backup before beginning the upgrade process.')?></p>
          <input type="button" class="btn btn-primary" id="doBackup" <?= 'value="'.gettext('Generate Database Backup').'"' ?>>
          <span id="backupStatus"></span>
          <div id="resultFiles" style="margin-top:10px">
          </div>
        </div>
      </div>
    </div>
    <div>
      <i class="fas fa-cloud-download-alt bg-blue"></i>
      <div class="timeline-item" >
        <h3 class="timeline-header"><?= gettext('Step 2: Fetch Update Package on Server') ?> <span id="status2"></span></h3>
        <div class="timeline-body" id="fetchPhase" style="display: none">
          <p><?= gettext('Fetch the latest files from the EcclesiaCRM GitHub release page')?></p>
          <input type="button" class="btn btn-primary" id="fetchUpdate" <?= 'value="'.gettext('Fetch Update Files').'"' ?> >
        </div>
      </div>
    </div>
    <div>
      <i class="fas fa-cogs bg-blue"></i>
      <div class="timeline-item" >
        <h3 class="timeline-header"><?= gettext('Step 3: Apply Update Package on Server') ?> <span id="status3"></span></h3>
        <div class="timeline-body" id="updatePhase" style="display: none">
          <p><?= gettext('Extract the upgrade archive, and apply the new files')?></p>
          <h4><?= gettext('Release Notes') ?></h4>
          <pre id="releaseNotes"></pre>
          <ul>
            <li><?= gettext('File Name:')?> <span id="updateFileName"> </span></li>
            <li><?= gettext('Full Path:')?> <span id="updateFullPath"> </span></li>
            <li><?= gettext('SHA1:')?> <span id="updateSHA1"> </span></li>
          </ul>
          <br/>
          <input type="button" class="btn btn-warning" id="applyUpdate" value="<?= gettext('Upgrade System') ?>">
        </div>
      </div>
    </div>
    <div>
      <i class="fas fa-sign-in-alt bg-blue"></i>
      <div class="timeline-item" >
        <h3 class="timeline-header"><?= gettext('Step 4: Login') ?></h3>
        <div class="timeline-body" id="finalPhase" style="display: none">
          <a href="Logoff.php" class="btn btn-primary"><?= gettext('Login to Upgraded System') ?> </a>
        </div>
      </div>
    </div>
  </div>
</div>
<script nonce="<?= SystemURLs::getCSPNonce() ?>">
 $("#doBackup").click(function(){
   $("#status1").html('<i class="far fa-circle-notch fa-spin"></i>');
   $.ajax({
      type        : 'POST', // define the type of HTTP verb we want to use (POST for our form)
      url         : window.CRM.root +'/api/database/backup', // the url where we want to POST
      data        : JSON.stringify({
        'iArchiveType'              : 3
      }), // our data object
      dataType    : 'json', // what type of data do we expect back from the server
      encode      : true,
      contentType: "application/json; charset=utf-8"
    })
    .done(function(data) {
      var downloadButton = "<button class=\"btn btn-primary\" id=\"downloadbutton\" role=\"button\" onclick=\"javascript:downloadbutton('"+data.filename+"')\"><i class='fas fa-download'></i>  "+data.filename+"</button>";
      $("#backupstatus").css("color","green");
      $("#backupstatus").html("<?= gettext('Backup Complete, Ready for Download.') ?>");
      $("#resultFiles").html(downloadButton);
      $("#status1").html('<i class="fas fa-check" style="color:orange"></i>');
      $("#downloadbutton").click(function(){
        $("#fetchPhase").show("slow");
        $("#backupPhase").slideUp();
        $("#status1").html('<i class="fas fa-check" style="color:green"></i>');
      });
    }).fail(function()  {
      $("#backupstatus").css("color","red");
      $("#backupstatus").html("<?= gettext('Backup Error.') ?>");
    });

 });

 $("#fetchUpdate").click(function(){
    $("#status2").html('<i class="far fa-circle-notch fa-spin"></i>');
    $.ajax({
      type : 'GET',
      url  : window.CRM.root +'/api/systemupgrade/downloadlatestrelease', // the url where we want to POST
      dataType    : 'json' // what type of data do we expect back from the server
    }).done(function(data){
      $("#status2").html('<i class="fas fa-check" style="color:green"></i>');
      window.CRM.updateFile=data;
      $("#updateFileName").text(data.fileName);
      $("#updateFullPath").text(data.fullPath);
      $("#releaseNotes").text(data.releaseNotes);
      $("#updateSHA1").text(data.sha1);
      $("#fetchPhase").slideUp();
      $("#updatePhase").show("slow");
    });

 });

 $("#applyUpdate").click(function(){
   $("#status3").html('<i class="far fa-circle-notch fa-spin"></i>');
   $.ajax({
      type : 'POST',
      url  : window.CRM.root +'/api/systemupgrade/doupgrade', // the url where we want to POST
      data        : JSON.stringify({
        fullPath: window.CRM.updateFile.fullPath,
        sha1: window.CRM.updateFile.sha1
      }), // our data object
      dataType    : 'json', // what type of data do we expect back from the server
      encode      : true,
      contentType: "application/json; charset=utf-8"
    }).done(function(data){
      $("#status3").html('<i class="fas fa-check" style="color:green"></i>');
      $("#updatePhase").slideUp();
      $("#finalPhase").show("slow");
    });
 });

function downloadbutton(filename) {
    window.location = window.CRM.root +"/api/database/download/"+filename;
    $("#backupstatus").css("color","green");
    $("#backupstatus").html("<?= gettext('Backup Downloaded, Copy on server removed') ?>");
    $("#downloadbutton").attr("disabled","true");
}
</script>

<?php
// Add the page footer
require 'Include/FooterNotLoggedIn.php';

// Turn OFF output buffering
ob_end_flush();
?>
