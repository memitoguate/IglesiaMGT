<?php

/*******************************************************************************
 *
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2017
 *
 ******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';

use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\dto\SystemURLs;

$type = gettext("Family");
if (!empty($_GET['type'])) {
    $type = gettext(InputUtils::FilterString($_GET['type']));
}

//Set the page title
$sPageTitle = gettext("Not Found") . ": " . gettext($type);
require '../Include/Header.php';


?>
<div class="error-page">
    <h2 class="headline text-yellow">404</h2>

    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-yellow"></i> <?= gettext("Oops!") . " " . strtoupper($type) . " " . gettext("Not Found") ?></h3>

        <p>
            <?= gettext("We could not find the member(s) you were looking for.") ?>
            <?= gettext("Meanwhile, you may")?> <a href="<?= SystemURLs::getRootPath() ?>/v2/dashboard"> <?= gettext("return to member dashboard") ?></a>
        </p>
    </div>
</div>

<?php
require '../Include/Footer.php';
?>
