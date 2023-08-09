<?php
/*******************************************************************************
 *
 *  filename    : peoplelist.php
 *  last change : 2020-03-08
 *  description : Philippe Logel All right reserved
 *
 ******************************************************************************/

use EcclesiaCRM\SessionUser;

// Security
require $sRootDocument . '/Include/Header.php';

$mode = "";

if ($sMode == 'person') {
    $mode = "*";
} else if ($sMode == 'family') {
    $mode = _("Families");
} else if ($sMode == 'single' or $sMode == 'singles') {
    $mode = _("Singles");
}
?>
<div
    class="card">
    <div class="card-header border-1">
        <h3 class="card-title"><i class="fas fa-filter"></i> <?= _('Filters') ?></h3>
    </div>
    <div class="card-body clearfix">
        <div class="row">
            <div class="col-sm-3"><?= _("Enter the search term") ?> :</div>
            <div class="col-sm-9">
                <select type="text" id="SearchTerm"
                       placeholder="<?= _("Search terms like : name, first name, phone number, property, group name, etc ...") ?>"
                       size="1" maxlength="100"
                        class="SearchTerm form-control form-control-sm" width="100%" style="width: 100%"></select>
            </div>
        </div>
        <br/>
        <div class="row help-filters">
            <div class="col-sm-3"><?= _("Filter Hints") ?> :</div>
            <div class="col-sm-9" style="color:orange">
                 <?= "*".", '"._("Singles")."', '"._("Volunteers")."', '"._("Families")."', '"._("Groups")."', '"._("Sunday Groups")."', '"._("groupmasters")."', <br/>" ?>
                 <?= _("phone number").", "._("first name").", "._("name").", "._("group name").", "._("check number").", "._("city").", "._("street").", "._("zip code")." "._("or what else")." .... " ?>
            </div>
        </div>
        <br/>
        <div class="row person-filters">
            <div class="col-sm-3"><?= _("Choose your person filters") ?> :</div>
            <div class="col-sm-9">
                <select name="search[]" multiple="" id="searchCombo" style="width:100%" size="1"
                        data-select2-id="searchList" tabindex="-1" aria-hidden="true"></select>
            </div>
        </div>
        <br/>
        <div class="row" id="group_search_filters">
            <div class="col-sm-3"><?= _("Group filters") ?> :</div>
            <div class="col-md-4">
                <select name="searchGroup[]" id="searchComboGroup" style="width:100%" size="1"
                        data-select2-id="searchListGroups" tabindex="-1" aria-hidden="true"></select>
            </div>
            <div class="col-md-4">
                <select name="searchGroupRole[]" id="searchComboGroupRole" style="width:100%" size="1"
                        data-select2-id="searchComboGroupRole" tabindex="-1" aria-hidden="true"></select>
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-sm-10">&nbsp;</div>
            <div class="col-md-2">
                <button type="button" class="btn btn-success" id="search_OK" class="right"><i class="fas fa-search"></i>  <?= _("Search") ?></button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header border-1">
        <h3 class="card-title"><i class="fas fa-search"></i> <?= _('Search Results') ?></h3>
        <div class="card-tools">
            <h3 class="in-progress" style="color:red"></h3>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-sm-12">
                <div style="text-align: center;">
                    <label>
                        <?= _("Results count:") ?>
                    </label>
                    <span id="numberOfPersons"></span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div style="text-align: center;">
                    <?php
                    if (SessionUser::getUser()->isShowCartEnabled()) {
                        ?>
                        <a id="AddPageToCart" class="btn btn-primary btn-sm"><?= _('Add This Page to Cart') ?></a>
                        <a id="RemovePageFromCart"
                           class="btn btn-danger btn-sm"><?= _('Remove This Page from Cart') ?></a><br><br>
                        <a id="AddAllToCart" class="btn btn-primary btn-sm"><?= _('Add All to Cart') ?></a>
                        <input name="IntersectCart" type="submit" class="btn btn-warning btn-sm"
                               value="<?= _('Intersect with Cart') ?>">&nbsp;
                        <a id="RemoveAllFromCart" class="btn btn-danger btn-sm"><?= _('Remove All from Cart') ?></a>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <br/>
        <table width="100%" cellpadding="2"
               class="table table-striped table-bordered data-table dataTable no-footer dtr-inline"
               id="DataSearchTable"></table>
    </div>
</div>

<script nonce="<?= $sCSPNonce ?>">
    window.CRM.mode = "<?= $mode ?>",
    window.CRM.listPeople = [];
    window.CRM.gender = <?= $iGender ?>;
    window.CRM.familyRole = <?= $iFamilyRole ?>;
    window.CRM.classification = <?= $iClassification ?>;
</script>

<script src="<?= $sRootPath ?>/skin/js/Search/Search.js"></script>
<script src="<?= $sRootPath ?>/skin/js/people/AddRemoveCart.js"></script>

<?php require $sRootDocument . '/Include/Footer.php'; ?>
