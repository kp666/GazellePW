<?

use Gazelle\Manager\Donation;
use Gazelle\Manager\PrepaidCardStatus;

if (!check_perms('users_give_donor')) {
    error(403);
}
$CountPerPage = 10;
$donation = new Donation();
list($Page, $Limit) = Format::page_limit($CountPerPage);
list($All, $Result) = $donation->getAllPrepaidCardDonations($Limit);
$PageView = Format::get_pages($Page, $All, $CountPerPage);
$Title = Lang::get('tools', 'prepaid_card_donor');
View::show_header($Title, 'PageToolPrepaidCard');
?>
<div class="BodyHeader">
    <h2 class="BodyHeader-nav"><?= $Title ?></h2>
</div>
<div class="LayoutBody">
    <div class="BodyNavLinks">
        <?= $PageView ?>
    </div>
    <div class="TableContainer">
        <table class="TableDonateManager Table">
            <tr class="Table-rowHeader">
                <td class="Table-cell">用户</td>
                <td class="Table-cell"><?= Lang::get('donate', 'added_time') ?></td>
                <td class="Table-cell"><?= Lang::get('donate', 'card_num') ?></td>
                <td class="Table-cell"><?= Lang::get('donate', 'card_secret') ?></td>
                <td class="Table-cell"><?= Lang::get('donate', 'face_value') ?></td>
                <td class="Table-cell"><?= Lang::get('tools', 'action') ?></td>
            </tr>
            <?
            $Row = 'a';
            foreach ($Result as $Item) {
                list($ID, $UserID, $CreateTime, $CardNum, $CardSecret, $FaceValue, $Status) = $Item;
            ?>
                <tr class="Table-row">
                    <form method="post">
                        <input type="hidden" name="action" value="take_prepaid_card">
                        <input type="hidden" name="id" value="<?= $ID ?>">

                        <td class="Table-cell"><?= Users::format_username($UserID) ?></td>
                        <td class="Table-cell"><?= $CreateTime ?></td>
                        <td class="Table-cell"><?= $CardNum ?></td>
                        <td class="Table-cell"><?= $CardSecret ?></td>
                        <td class="Table-cell"><?= $FaceValue ?></td>
                        <td class="Table-cell">
                            <?
                            if ($Status == PrepaidCardStatus::Reject) {
                            ?>
                                <span class="u-colorWarning"><?= Lang::get('tools', 'rejected') ?></span>
                            <?
                            } else if ($Status == PrepaidCardStatus::Passed) {
                            ?>
                                <span class="u-colorSuccess"><?= Lang::get('tools', "passed") ?></span>
                            <?
                            } else {
                            ?>
                                <button class="Button" type="submit" name="result" value="2" onclick="return confirm('<?= Lang::get('tools', 'sure_delete_staff_group_title') ?>')"><?= Lang::get('tools', 'pass') ?></button>
                                <button class="Button" type="submit" name="result" value="3" onclick="return confirm('<?= Lang::get('tools', 'sure_delete_staff_group_title') ?>')"><?= Lang::get('tools', 'reject') ?></button>
                            <?
                            }
                            ?>
                        </td>
                    </form>
                </tr>
            <?  } ?>
            </tr>
        </table>
    </div>
    <div class="BodyNavLinks">
        <?= $PageView ?>
    </div>

</div>
<?
View::show_footer();
