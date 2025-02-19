<?
if (!check_perms('users_mod')) {
    error(403);
}

if (isset($_GET['userid']) && is_number($_GET['userid'])) {
    $UserHeavyInfo = Users::user_heavy_info($_GET['userid']);
    if (isset($UserHeavyInfo['torrent_pass'])) {
        $TorrentPass = $UserHeavyInfo['torrent_pass'];
        $UserPeerStats = Tracker::user_peer_count($TorrentPass);
        $UserInfo = Users::user_info($_GET['userid']);
        $UserLevel = $Classes[$UserInfo['PermissionID']]['Level'];
        if (!check_paranoia('leeching+', $UserInfo['Paranoia'], $UserLevel, $_GET['userid'])) {
            $UserPeerStats[0] = false;
        }
        if (!check_paranoia('seeding+', $UserInfo['Paranoia'], $UserLevel, $_GET['userid'])) {
            $UserPeerStats[1] = false;
        }
    } else {
        $UserPeerStats = false;
    }
} else {
    $MainStats = Tracker::info();
}

View::show_header(Lang::get('tools', 'tracker_info'), '', 'PageToolOcelotInfo');
?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= Lang::get('tools', 'tracker_info') ?></h2>
    </div>
    <div class="BodyNavLinks">
        <a href="?action=<?= $_REQUEST['action'] ?>" class="brackets"><?= Lang::get('tools', 'main_stats') ?></a>
    </div>
    <div class="LayoutMainSidebar">
        <div class="Sidebar LayoutMainSidebar-sidebar">
            <div class="SidebarItem Box">
                <div class="SidebarItem-header Box-header">
                    <strong><?= Lang::get('tools', 'user_stats') ?></strong>
                </div>
                <div class="SidebarItem-body Box-body">
                    <form class="FormOneLine FormToolUserStat" method="get" action="">
                        <input type="hidden" name="action" value="ocelot_info" />
                        <span class="label"><?= Lang::get('tools', 'get_stats_for_user') ?></span><br />
                        <input class="Input" type="text" name="userid" placeholder="<?= Lang::get('tools', 'user_id') ?>" value="<? Format::form('userid') ?>" />
                        <input class="Button" type="submit" value="Go" />
                    </form>
                </div>
            </div>
        </div>
        <div class="LayoutMainSidebar-main">
            <div class="box box2">
                <div class="head"><strong><?= Lang::get('tools', 'numbers_and_such') ?></strong></div>
                <div class="pad">
                    <?
                    if (!empty($UserPeerStats)) {
                    ?>
                        <?= Lang::get('tools', 'user_id') ?>: <?= $_GET['userid'] ?><br />
                        <?= Lang::get('tools', 'leeching') ?>: <?= $UserPeerStats[0] === false ? "hidden" : number_format($UserPeerStats[0]) ?><br />
                        <?= Lang::get('tools', 'seeding') ?>: <?= $UserPeerStats[1] === false ? "hidden" : number_format($UserPeerStats[1]) ?><br />
                        <?
                    } elseif (!empty($MainStats)) {
                        foreach ($MainStats as $Key => $Value) {
                            if (is_numeric($Value)) {
                                if (substr($Key, 0, 6) === "bytes ") {
                                    $Value = Format::get_size($Value);
                                    $Key = substr($Key, 6);
                                } else {
                                    $Value = number_format($Value);
                                }
                            }
                        ?>
                            <?= "$Value $Key<br />\n" ?>
                        <?
                        }
                    } elseif (isset($TorrentPass)) {
                        ?>
                        <?= Lang::get('tools', 'failed_to_get_stats_for_user_before') ?><?= $_GET['userid'] ?><?= Lang::get('tools', 'failed_to_get_stats_for_user_after') ?>
                    <?
                    } elseif (isset($_GET['userid'])) {
                    ?>
                        <?= Lang::get('tools', 'user_does_not_exist_before') ?><?= display_str($_GET['userid']) ?><?= Lang::get('tools', 'user_does_not_exist_after') ?>
                    <?
                    } else {
                    ?>
                        <?= Lang::get('tools', 'failed_to_get_tracker_info') ?>
                    <?
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?
View::show_footer();
