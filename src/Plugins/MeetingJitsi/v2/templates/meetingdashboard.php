<?php
/*******************************************************************************
 *
 *  filename    : meetingdashboard.php
 *  last change : 2020-07-04
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : 2020 Philippe Logel all right reserved not MIT licence
 *                This code can't be included in another software
 *
 ******************************************************************************/

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\OutputUtils;
use EcclesiaCRM\dto\ChurchMetaData;

require $sRootDocument . '/Include/Header.php';
?>

<div class="card bg-gray-dark card-body">
    <div class="margin">
        <label><?= dgettext("messages-MeetingJitsi","Room names") ?></label>
        <div class="btn-group">
            <a class="btn btn-app" id="newRoom" data-toggle="tooltip" data-placement="bottom" title="<?= dgettext("messages-MeetingJitsi","Create first a room and manage theme with the arrow button") ?>"><i
                    class="fas fa-sticky-note"></i><?= dgettext("messages-MeetingJitsi","Create Room") ?></a>
            <button type="button" class="btn btn-app dropdown-toggle" data-toggle="dropdown">
                <span class="caret"></span>
                <span class="sr-only">Menu déroulant</span>
            </button>
            <div class="dropdown-menu" role="menu">
                <?php foreach ($allRooms as $room) { ?>
                    <a class="dropdown-item selectRoom" data-roomid="<?= $room['Id'] ?>">
                        (<?= (new DateTime($room['CreationDate']))->format(SystemConfig::getValue('sDateFormatLong')) ?>)
                        <?= $room['Code'] ?>
                        </a>
                <?php } ?>
            </div>
            &nbsp;
            <a class="btn btn-app bg-danger" id="delete-all-rooms" data-toggle="tooltip"  data-placement="bottom" title="<?= dgettext("messages-MeetingJitsi","This action will delete all your rooms") ?>">
                <i class="fas fa-times-circle"></i><?= dgettext("messages-MeetingJitsi","Delete all Rooms") ?>
            </a>
            &nbsp;
            <a class="btn btn-app bg-orange" id="add-event"><i class="far fa-calendar-plus"></i><?= dgettext("messages-MeetingJitsi","Appointment") ?>
            </a>
        </div>
    </div>
</div>

<div class="row" style="height: 100%">
    <div class="col-md-12">
        <div class="card card-gray-dark">
            <div class="card-header  border-1">
                <div
                    class="card-title"><?= dgettext("messages-MeetingJitsi","Room Name") ?> : <?= $roomName ?>
                </div>
                <div style="float:right"><a href="https://jitsi.org/" target="_blank">
                        <img src="<?= $sRootPath ?>/Images/jitsi_logo.png" height="25/"></a>
                </div>
            </div>
            <div class="card-body" style="padding: 0px">
                <div id="meetingIframe" style="width:100%;height:600px">
                    <?php if ($roomName == '') { ?>
                        <div class="text-center">
                            <img src="<?= $sRootPath ?>/Images/jitsi_logo.png" height="85/"><br/>
                            <label> <?= dgettext("messages-MeetingJitsi","First create a Jitsi room with the button above !") ?></label>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require $sRootDocument . '/Include/Footer.php'; ?>

<script src='<?= $domainscriptpath ?>'></script>

<link href="<?= $sRootPath ?>/skin/external/bootstrap-colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet">

<script src="<?= $sRootPath ?>/skin/external/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>
<script src="<?= $sRootPath ?>/skin/external/bootstrap-colorpicker/bootstrap-colorpicker.min.js"
        type="text/javascript"></script>

<script src="<?= $sRootPath ?>/skin/external/ckeditor/ckeditor.js"></script>
<script src="<?= $sRootPath ?>/skin/js/ckeditor/ckeditorextension.js"></script>


<script nonce="<?= $sCSPNonce ?>">
    <?php if ($roomName != '') { ?>
    // jitsi code
    const domain = '<?= $domain ?>';
    const options = {
        roomName: '<?= $roomName ?>',
        width: '100%',
        height: '100%',
        parentNode: document.querySelector('#meetingIframe')
    };
    const api = new JitsiMeetExternalAPI(domain, options);
    // end
    <?php } ?>

    // page construction
    var sPageTitle = '<?= $sPageTitle ?>';

    window.CRM.churchloc = {
        lat: <?= OutputUtils::number_dot(ChurchMetaData::getChurchLatitude()) ?>,
        lng: <?= OutputUtils::number_dot(ChurchMetaData::getChurchLongitude()) ?>};
    window.CRM.mapZoom = <?= SystemConfig::getValue("iLittleMapZoom")?>;
</script>


<script src="<?= $sRootPath ?>/skin/js/calendar/EventEditor.js"></script>
<script src="<?= $sRootPath ?>/Plugins/MeetingJitsi/skin/js/meeting.js"></script>

<?php
if (SystemConfig::getValue('sMapProvider') == 'OpenStreetMap') {
    ?>
    <script src="<?= $sRootPath ?>/skin/js/calendar/OpenStreetMapEvent.js"></script>
<?php
} else if (SystemConfig::getValue('sMapProvider') == 'GoogleMaps') {
?>
    <!--Google Map Scripts -->
    <script
        src="https://maps.googleapis.com/maps/api/js?key=<?= SystemConfig::getValue('sGoogleMapKey') ?>"></script>

    <script src="<?= $sRootPath ?>/skin/js/calendar/GoogleMapEvent.js"></script>
<?php
} else if (SystemConfig::getValue('sMapProvider') == 'BingMaps') {
?>
    <script src="<?= $sRootPath ?>/skin/js/calendar/BingMapEvent.js"></script>
    <?php
}
?>

