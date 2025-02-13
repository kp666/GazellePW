<?
/*
 * This is the outline page for auto reports. It calls the AJAX functions
 * that actually populate the page and shows the proper header and footer.
 * The important function is AddMore().
 */
if (!check_perms('admin_reports')) {
    error(403);
}

View::show_header(Lang::get('reportsv2', 'reports_v2'), 'reportsv2', 'PageReportV2Home');
?>
<div class="BodyHeader">
    <h2 class="BodyHeader-nav"><?= Lang::get('reportsv2', 'new_reports_auto_assigned') ?></h2>
    <? include('header.php'); ?>
</div>
<div class="buttonBoxBody center">
    <input class="Button" type="button" onclick="AddMore();" value="Add more" />
    <input class="Input" type="text" name="repop_amount" id="repop_amount" size="2" value="10" />
    | <span data-tooltip="<?= Lang::get('reportsv2', 'dynamic_title') ?>">
        <input type="checkbox" checked="checked" id="dynamic" /> <label for="dynamic"><?= Lang::get('reportsv2', 'dynamic') ?></label></span>
    | <span data-tooltip="<?= Lang::get('reportsv2', 'multi_resolve_btn_title') ?>">
        <input class="Button" type="button" onclick="MultiResolve();" value="<?= Lang::get('reportsv2', 'multi_resolve') ?>" /></span>
    | <span data-tooltip="<?= Lang::get('reportsv2', 'unclaim_all_btn_title') ?>">
        <input class="Button" type="button" onclick="GiveBack();" value="<?= Lang::get("reportsv2", 'unclaim_all') ?>" /></span>
</div>

<div id="all_reports" style="margin-left: auto; margin-right: auto;">
</div>

<?
View::show_footer();
?>