<?

/************************************************************************
 ************************************************************************/
if (!check_perms('admin_reports') && !check_perms('project_team') && !check_perms('site_moderate_forums')) {
    error(404);
}

// Number of reports per page
define('REPORTS_PER_PAGE', '10');

list($Page, $Limit) = Format::page_limit(REPORTS_PER_PAGE);

include(CONFIG['SERVER_ROOT'] . '/sections/reports/array.php');

// Header
View::show_header(Lang::get('reports', 'reports'), 'bbcode,reports', 'PageReportHome');

if (isset($_GET['id']) && is_number($_GET['id'])) {
    $View = 'Single report';
    $Where = 'r.ID = ' . $_GET['id'];
} elseif (empty($_GET['view'])) {
    $View = 'New';
    $Where = "Status = 'New'";
} else {
    $View = $_GET['view'];
    switch ($_GET['view']) {
        case 'old':
            $Where = "Status = 'Resolved'";
            break;
        default:
            error(404);
            break;
    }
}

if (!check_perms('admin_reports')) {
    if (check_perms('project_team')) {
        $Where .= " AND Type = 'request_update'";
    }
    if (check_perms('site_moderate_forums')) {
        $Where .= " AND Type IN('comment', 'post', 'thread')";
    }
}

$Reports = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		r.ID,
		r.UserID,
		um.Username,
		r.ThingID,
		r.Type,
		r.ReportedTime,
		r.Reason,
		r.Status,
		r.ClaimerID,
		r.Notes,
		r.ResolverID
	FROM reports AS r
		JOIN users_main AS um ON r.UserID = um.ID
	WHERE $Where
	ORDER BY ReportedTime DESC
	LIMIT $Limit");

// Number of results (for pagination)
$DB->query('SELECT FOUND_ROWS()');
list($Results) = $DB->next_record();

// Done with the number of results. Move $DB back to the result set for the reports
$DB->set_query_id($Reports);

// Start printing stuff
?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= Lang::get('reports', 'active_reports') ?></h2>
        <div class="BodyNavLinks">
            <a href="reports.php"><?= Lang::get('reports', 'new') ?></a>
            <a href="reports.php?view=old"><?= Lang::get('reports', 'old') ?></a>
            <a href="reports.php?action=stats"><?= Lang::get('reports', 'stats') ?></a>
        </div>
    </div>
    <div class="BodyNavLinks">
        <?
        // pagination
        $Pages = Format::get_pages($Page, $Results, REPORTS_PER_PAGE, 11);
        echo $Pages;
        ?>
    </div>
    <?
    while (list($ReportID, $SnitchID, $SnitchName, $ThingID, $Short, $ReportedTime, $Reason, $Status, $ClaimerID, $Notes, $ResolverID) = $DB->next_record()) {
        $Type = $Types[$Short];
        $Reference = "reports.php?id=$ReportID#report$ReportID";
    ?>
        <div class="TableContainer pending_report_v1" id="report_<?= $ReportID ?>" style="margin-bottom: 1em;">
            <table class="Table" cellpadding="5" id="report_<?= $ReportID ?>">
                <tr class="Table-rowHeader">
                    <td class="Table-cell">
                        <strong><a href="<?= $Reference ?>"><?= Lang::get('reports', 'report') ?> #<?= $ReportID ?></a></strong>
                    </td>
                    <td class="Table-cell">
                        <strong><?= $Type['title'] ?></strong><?= Lang::get('reports', 'sth_was_reported_by_user_sometime_before') ?><a href="user.php?id=<?= $SnitchID ?>"><?= $SnitchName ?></a><?= Lang::get('reports', 'sth_was_reported_by_user_sometime_after') ?><?= time_diff($ReportedTime) ?>
                        <a href="reports.php?action=compose&amp;to=<?= $SnitchID ?>&amp;reportid=<?= $ReportID ?>&amp;type=<?= $Short ?>&amp;thingid=<?= $ThingID ?>" class="brackets"><?= Lang::get('reports', 'contact') ?></a>
                    </td>
                </tr>
                <tr class="Table-row">
                    <td class="Table-cell center" colspan="2">
                        <strong>
                            <? switch ($Short) {
                                case 'user':
                                    $DB->query("
										SELECT Username
										FROM users_main
										WHERE ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_user_with_the_reported_id_found');
                                    } else {
                                        list($Username) = $DB->next_record();
                                        echo "<a href=\"user.php?id=$ThingID\">" . display_str($Username) . '</a>';
                                    }
                                    break;
                                case 'request':
                                case 'request_update':
                                    $DB->query("
										SELECT Title
										FROM requests
										WHERE ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_user_with_the_reported_id_found');
                                    } else {
                                        list($Name) = $DB->next_record();
                                        echo "<a href=\"requests.php?action=view&amp;id=$ThingID\">" . display_str($Name) . '</a>';
                                    }
                                    break;
                                case 'collage':
                                    $DB->query("
										SELECT Name
										FROM collages
										WHERE ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_collage_with_the_reported_id_found');
                                    } else {
                                        list($Name) = $DB->next_record();
                                        echo "<a href=\"collages.php?id=$ThingID\">" . display_str($Name) . '</a>';
                                    }
                                    break;
                                case 'thread':
                                    $DB->query("
										SELECT Title
										FROM forums_topics
										WHERE ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_forum_thread_with_the_reported_id_found');
                                    } else {
                                        list($Title) = $DB->next_record();
                                        echo "<a href=\"forums.php?action=viewthread&amp;threadid=$ThingID\">" . display_str($Title) . '</a>';
                                    }
                                    break;
                                case 'post':
                                    if (isset($LoggedUser['PostsPerPage'])) {
                                        $PerPage = $LoggedUser['PostsPerPage'];
                                    } else {
                                        $PerPage = CONFIG['POSTS_PER_PAGE'];
                                    }
                                    $DB->query("
										SELECT
											p.ID,
											p.Body,
											p.TopicID,
											(
												SELECT COUNT(p2.ID)
												FROM forums_posts AS p2
												WHERE p2.TopicID = p.TopicID
													AND p2.ID <= p.ID
											) AS PostNum
										FROM forums_posts AS p
										WHERE p.ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_forum_post_with_the_reported_id_found');
                                    } else {
                                        list($PostID, $Body, $TopicID, $PostNum) = $DB->next_record();
                                        echo "<a href=\"forums.php?action=viewthread&amp;threadid=$TopicID&amp;post=$PostNum#post$PostID\">FORUM POST ID #$PostID</a>";
                                    }
                                    break;
                                case 'comment':
                                    $DB->query("
										SELECT 1
										FROM comments
										WHERE ID = $ThingID");
                                    if (!$DB->has_results()) {
                                        echo Lang::get('reports', 'no_comment_with_the_reported_id_found');
                                    } else {
                                        echo "<a href=\"comments.php?action=jump&amp;postid=$ThingID\">COMMENT</a>";
                                    }
                                    break;
                            }
                            ?>
                        </strong>
                    </td>
                </tr>
                <tr class="Table-row">
                    <td class="Table-cell" colspan="2">
                        <div class="HtmlText">
                            <?= Text::full_format($Reason) ?>
                        </div>
                    </td>
                </tr>
                <tr class="Table-row">
                    <td class="Table-cell" colspan="2">
                        <? if ($ClaimerID == $LoggedUser['ID']) { ?>
                            <span id="claimed_<?= $ReportID ?>"><?= Lang::get('reports', 'claimed_by_before') ?><?= Users::format_username($ClaimerID, false, false, false, false) ?><?= Lang::get('reports', 'claimed_by_after') ?> <a href="#" onclick="unClaim(<?= $ReportID ?>); return false;" class="brackets"><?= Lang::get('reports', 'unclaim') ?></a></span>
                        <? } elseif ($ClaimerID) { ?>
                            <span id="claimed_<?= $ReportID ?>"><?= Lang::get('reports', 'claimed_by_before') ?><?= Users::format_username($ClaimerID, false, false, false, false) ?><?= Lang::get('reports', 'claimed_by_after') ?></span>
                        <? } else { ?>
                            <a href="#" id="claim_<?= $ReportID ?>" onclick="claim(<?= $ReportID ?>); return false;" class="brackets"><?= Lang::get('reports', 'claim') ?></a>
                        <? } ?>
                        &nbsp;&nbsp;
                        <a href="#" onclick="toggleNotes(<?= $ReportID ?>); return false;" class="brackets"><?= Lang::get('reports', 'toggle_notes') ?></a>

                        <div id="notes_div_<?= $ReportID ?>" style="display: <?= empty($Notes) ? 'none' : 'block'; ?>;">
                            <textarea class="Input" cols="50" rows="3" id="notes_<?= $ReportID ?>"><?= $Notes ?></textarea>
                            <br />
                            <input class="Button" type="submit" onclick="saveNotes(<?= $ReportID ?>)" value="Save" />
                        </div>
                    </td>
                </tr>
                <? if ($Status != 'Resolved') { ?>
                    <tr class="Table-row">
                        <td class="Table-cell center" colspan="2">
                            <form id="report_form_<?= $ReportID ?>" action="">
                                <input type="hidden" name="reportid" value="<?= $ReportID ?>" />
                                <input type="hidden" name="auth" value="<?= $LoggedUser['AuthKey'] ?>" />
                                <input class="Button" type="submit" onclick="return resolve(<?= $ReportID ?>, <?= (($ClaimerID == $LoggedUser['ID'] || !$ClaimerID) ? 'true' : 'false') ?>)" name="submit" value="Resolve" />
                            </form>
                        </td>
                    </tr>
                <?  } else { ?>
                    <? $ResolverInfo = Users::user_info($ResolverID); ?>
                    <tr class="Table-row">
                        <td class="Table-cell" colspan="2">
                            <?= Lang::get('reports', 'resolved_by_before') ?><a href="users.php?id=<?= $ResolverID ?>"><?= $ResolverInfo['Username'] ?></a><?= Lang::get('reports', 'resolved_by_after') ?>
                        </td>
                    </tr>
                <? } ?>
            </table>
        </div>
        <? $DB->set_query_id($Reports); ?>
    <? } ?>
    <div class="BodyNavLinks">
        <?= $Pages; ?>
    </div>
</div>
<?
View::show_footer();
