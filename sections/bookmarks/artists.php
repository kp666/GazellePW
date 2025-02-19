<?

if (!empty($_GET['userid'])) {
    if (!check_perms('users_override_paranoia')) {
        error(403);
    }
    $UserID = $_GET['userid'];
    $Sneaky = $UserID !== $LoggedUser['ID'];
    if (!is_number($UserID)) {
        error(404);
    }
    $DB->query("
		SELECT Username
		FROM users_main
		WHERE ID = '$UserID'");
    list($Username) = $DB->next_record();
} else {
    $UserID = $LoggedUser['ID'];
}

$Sneaky = $UserID !== $LoggedUser['ID'];

//$ArtistList = Bookmarks::all_bookmarks('artist', $UserID);

$DB->query("
	SELECT ag.ArtistID, ag.Name
	FROM bookmarks_artists AS ba
		INNER JOIN artists_group AS ag ON ba.ArtistID = ag.ArtistID
	WHERE ba.UserID = $UserID
	ORDER BY ag.Name");

$ArtistList = $DB->to_array();

$Title = $Sneaky ? "$Username" . Lang::get('bookmarks', 's_bookmarked_artists') : Lang::get('bookmarks', 'your_bookmarked_artists');

View::show_header($Title, 'browse', 'PageBookmarkArtists');

?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= $Title ?></h2>
        <div class="BodyNavLinks">
            <a href="bookmarks.php?type=torrents" class="brackets"><?= Lang::get('global', 'torrents') ?></a>
            <a href="bookmarks.php?type=artists" class="brackets"><?= Lang::get('global', 'artists') ?></a>
            <?
            if (CONFIG['ENABLE_COLLAGES']) {
            ?>
                <a href="bookmarks.php?type=collages" class="brackets"><?= Lang::get('bookmarks', 'collages') ?></a>
            <?
            }
            ?>
            <a href="bookmarks.php?type=requests" class="brackets"><?= Lang::get('global', 'requests') ?></a>
        </div>
    </div>
    <div class="Box">
        <div class="Box-body" align="center">
            <? if (count($ArtistList) === 0) { ?>
                <h2><?= Lang::get('bookmarks', 'no_bookmarked_artists') ?></h2>
        </div>
    </div>
</div>
<!--content-->
<?
                View::show_footer();
                die();
            } ?>
<div class="TableContainer">
    <table class="TableArtist Table">
        <tr class="Table-rowHeader">
            <td class="Table-cell"><?= Lang::get('global', 'artist') ?></td>
        </tr>
        <?
        $Row = 'a';
        foreach ($ArtistList as $Artist) {
            $Row = $Row === 'a' ? 'b' : 'a';
            list($ArtistID, $Name) = $Artist;
        ?>
            <tr class="Table-row bookmark_<?= $ArtistID ?>">
                <td class="Table-cell">
                    <a href="artist.php?id=<?= $ArtistID ?>"><?= $Name ?></a>
                    <span style="float: right;">
                        <?
                        if (check_perms('site_torrents_notify')) {
                            if (($Notify = $Cache->get_value('notify_artists_' . $LoggedUser['ID'])) === false) {
                                $DB->query("
				SELECT ID, Artists
				FROM users_notify_filters
				WHERE UserID = '$LoggedUser[ID]'
					AND Label = 'Artist notifications'
				LIMIT 1");
                                $Notify = $DB->next_record(MYSQLI_ASSOC);
                                $Cache->cache_value('notify_artists_' . $LoggedUser['ID'], $Notify, 0);
                            }
                            if (stripos($Notify['Artists'], "|$Name|") === false) {
                        ?>
                                <a href="artist.php?action=notify&amp;artistid=<?= $ArtistID ?>&amp;auth=<?= $LoggedUser['AuthKey'] ?>" class="brackets"><?= Lang::get('bookmarks', 'notify_new') ?></a>
                            <?
                            } else {
                            ?>
                                <a href="artist.php?action=notifyremove&amp;artistid=<?= $ArtistID ?>&amp;auth=<?= $LoggedUser['AuthKey'] ?>" class="brackets"><?= Lang::get('bookmarks', 'dont_notify_new') ?></a>
                        <?
                            }
                        }
                        ?>
                        &nbsp;&nbsp;&nbsp;<a href="#" id="bookmarklink_artist_<?= $ArtistID ?>" onclick="Unbookmark('artist', <?= $ArtistID ?>, '<?= Lang::get('bookmarks', 'bookmark') ?>'); return false;" class="brackets"><?= Lang::get('global', 'remove_bookmark') ?></a>
                    </span>
                </td>
            </tr>
        <?
        }
        ?>
    </table>
</div>
</div>
</div>
<?
View::show_footer();
$Cache->cache_value('bookmarks_' . $UserID, serialize(array(array($Username, $TorrentList, $CollageDataList))), 3600);
?>