<?
include(CONFIG['SERVER_ROOT'] . '/classes/torrenttable.class.php');
require(CONFIG['SERVER_ROOT'] . '/classes/top10_movies.class.php');

$Top10Movies = new Top10Movies();
$Where = array();
if (!empty($_GET['advanced']) && check_perms('site_advanced_top10')) {
    $Details = 'all';
    $Limit = 10;
} else {
    // error out on invalid requests (before caching)
    if (isset($_GET['details'])) {
        if (in_array($_GET['details'], array('day', 'week', 'overall', 'snatched', 'data', 'seeded', 'month', 'year'))) {
            $Details = $_GET['details'];
        } else {
            error(404);
        }
    } else {
        $Details = 'all';
    }

    // defaults to 10 (duh)
    $Limit = (isset($_GET['limit']) ? intval($_GET['limit']) : 10);
    $Limit = (in_array($Limit, array(10, 100, 250)) ? $Limit : 10);
}
$Filtered = !empty($Where);
View::show_header(Lang::get('top10', 'top') . " $Limit " . Lang::get('top10', 'top_movies'), '', 'PageTop10Home');
?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= Lang::get('top10', 'top') ?> <?= $Limit ?> <?= Lang::get('top10', 'top_movies') ?></h2>
        <? Top10View::render_linkbox("movies", "BodyNavLinks"); ?>
    </div>
    <?
    if ($Details == 'all' || $Details == 'day') {
        $Data = $Top10Movies->getData("active_day", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'in_the_past_day', false, [], Lang::get('top10', 'movies')), 'day', $Data, $Limit);
    }
    if ($Details == 'all' || $Details == 'week') {
        $Data = $Top10Movies->getData("active_week", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'in_the_past_week', false, [], Lang::get('top10', 'movies')), 'week', $Data, $Limit);
    }
    if ($Details == 'all' || $Details == 'month') {
        $Data = $Top10Movies->getData("active_month", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'in_the_past_month', false, [], Lang::get('top10', 'movies')), 'month', $Data, $Limit);
    }
    if ($Details == 'all' || $Details == 'year') {
        $Data = $Top10Movies->getData("active_year", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'in_the_past_year', false, [], Lang::get('top10', 'movies')), 'year', $Data, $Limit);
    }
    if ($Details == 'all' || $Details == 'overall') {
        $Data = $Top10Movies->getData("active_all", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'most_torrents', false, [], Lang::get('top10', 'movies')), 'overall', $Data, $Limit);
    }

    if (($Details == 'all' || $Details == 'snatched') && !$Filtered) {
        $Data = $Top10Movies->getData("snatched", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'most_snatched', false, [], Lang::get('top10', 'movies')), 'snatched', $Data, $Limit);
    }

    if (($Details == 'all' || $Details == 'data') && !$Filtered) {
        $Data = $Top10Movies->getData("data", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'most_data', false, [], Lang::get('top10', 'movies')), 'data', $Data, $Limit);
    }

    if (($Details == 'all' || $Details == 'seeded') && !$Filtered) {
        $Data = $Top10Movies->getData("seeded", ['Limit' => $Limit]);
        generate_torrent_table(Lang::get('top10', 'most_seed', false, [], Lang::get('top10', 'movies')), 'seeded', $Data, $Limit);
    }
    ?>
</div>
<?
View::show_footer();

function generate_torrent_table($Caption, $Tag, $Groups, $Limit) {
    if (empty($Groups)) {
        return null;
    }
?>
    <h3>
        <?= Lang::get('top10', 'top') ?> <?= "$Limit $Caption" ?>
        <? if (empty($_GET['advanced'])) { ?>
            <small class="top10_quantity_links">
                <?
                switch ($Limit) {
                    case 100: ?>
                        - <a class="brackets" href="top10.php?details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 10</a>
                        - <span class="brackets"><?= Lang::get('top10', 'top') ?> 100</span>
                        - <a class="brackets" href="top10.php?type=movies&amp;limit=250&amp;details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 250</a>
                    <? break;
                    case 250: ?>
                        - <a class="brackets" href="top10.php?details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 10</a>
                        - <a class="brackets" href="top10.php?type=movies&amp;limit=100&amp;details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 100</a>
                        - <span class="brackets"><?= Lang::get('top10', 'top') ?> 250</span>
                    <? break;
                    default: ?>
                        - <span class="brackets"><?= Lang::get('top10', 'top') ?> 10</span>
                        - <a class="brackets" href="top10.php?type=movies&amp;limit=100&amp;details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 100</a>
                        - <a class="brackets" href="top10.php?type=movies&amp;limit=250&amp;details=<?= $Tag ?>"><?= Lang::get('top10', 'top') ?> 250</a>
                <? } ?>
            </small>
        <? } ?>
    </h3>
    <?
    $tableRender = new TorrentGroupCoverTableView($Groups);
    $tableRender->render();
    ?>
<? } ?>