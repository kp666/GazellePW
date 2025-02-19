<?
if (!isset($_GET['groupid']) || !is_number($_GET['groupid'])) {
    error(0);
}
$GroupID = (int)$_GET['groupid'];

$Name = Torrents::group_name(Torrents::get_group($GroupID));

View::show_header(Lang::get('torrents', 'revision_history_after'), '', 'PageTorrentHistory');
?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= page_title_conn([Lang::get('torrents', 'revision_history_after'), $Name]) ?></h2>
    </div>
    <?
    RevisionHistoryView::render_revision_history(RevisionHistory::get_revision_history('torrents', $GroupID), "torrents.php?id=$GroupID");
    ?>
</div>
<?
View::show_footer();
