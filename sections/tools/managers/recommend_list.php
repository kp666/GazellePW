<?
if (!check_perms('site_recommend_own') && !check_perms('site_manage_recommendations')) {
    error(403);
}
View::show_header(Lang::get('tools', 'recommendations'));

$DB->query("
	SELECT
		tr.GroupID,
		tr.UserID,
		tg.Name,
		tg.ArtistID,
		ag.Name
	FROM torrents_recommended AS tr
		JOIN torrents_group AS tg ON tg.ID=tr.GroupID
		LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
	ORDER BY tr.Time DESC
	LIMIT 10
	");
?>
<div class="LayoutBody">
    <div class="box" id="recommended">
        <div class="head colhead_dark"><strong><?= Lang::get('tools', 'recommendations') ?></strong></div>
        <? if (!in_array($LoggedUser['ID'], $DB->collect('UserID'))) { ?>
            <form class="add_form" name="recommendations" action="tools.php" method="post" class="pad">
                <input type="hidden" name="action" value="recommend_add" />
                <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                <table cellpadding="6" cellspacing="1" border="0" class="layout border" width="100%">
                    <tr>
                        <td rowspan="2" class="label"><strong><?= Lang::get('tools', 'add_recommendation') ?>:</strong></td>
                        <td><?= Lang::get('tools', 'link_to_a_torrent_group') ?><strong><?= site_url() ?>torrents.php?id=12345</strong></td>
                    </tr>
                    <tr>
                        <td>
                            <input class="Input" type="text" name="url" size="50" />
                            <input class="Button" type="submit" value="Add recommendation" />
                        </td>
                    </tr>
                </table>
            </form>
        <?      } ?>
        <ul class="nobullet">
            <?
            while (list($GroupID, $UserID, $GroupName, $ArtistID, $ArtistName) = $DB->next_record()) {
            ?>
                <li>
                    <strong><?= Users::format_username($UserID, false, false, false) ?></strong>
                    <? if ($ArtistID) { ?>
                        - <a href="artist.php?id=<?= $ArtistID ?>"><?= $ArtistName ?></a>
                    <?      } ?>
                    - <a href="torrents.php?id=<?= $GroupID ?>"><?= $GroupName ?></a>
                    <? if (check_perms('site_manage_recommendations') || $UserID == $LoggedUser['ID']) { ?>
                        <a href="tools.php?action=recommend_alter&amp;groupid=<?= $GroupID ?>" class="brackets"><?= Lang::get('global', 'delete') ?></a>
                    <?      } ?>
                </li>
            <?  } ?>
        </ul>
    </div>
</div>
<? View::show_footer(); ?>