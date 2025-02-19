<?php

View::show_header(Lang::get('staffpm', 'staff_inbox'), '', 'PageStaffPMInbox');

$View = display_str($_GET['view']);
$UserLevel = $LoggedUser['EffectiveClass'];


$LevelCap = 1000;


// Setup for current view mode
$SortStr = 'IF(AssignedToUser = ' . $LoggedUser['ID'] . ', 0, 1) ASC, ';
switch ($View) {
    case 'unanswered':
        $ViewString = Lang::get('staffpm', 'unanswered');
        $Status = "Unanswered";
        break;
    case 'open':
        $ViewString = Lang::get('staffpm', 'unresolved');
        $Status = "Open', 'Unanswered";
        $SortStr = '';
        break;
    case 'resolved':
        $ViewString = Lang::get('staffpm', 'resolved');
        $Status = "Resolved";
        $SortStr = '';
        break;
    case 'my':
        $ViewString = Lang::get('staffpm', 'your_unanswered');
        $Status = "Unanswered";
        break;
    default:
        $Status = "Unanswered";
        if ($UserLevel >= $Classes[CONFIG['USER_CLASS']['FORUM_MOD']]['Level']) {
            $ViewString = Lang::get('staffpm', 'your_unanswered');
        } else {
            // FLS
            $ViewString = Lang::get('staffpm', 'unanswered');
        }
        break;
}

$WhereCondition = "
	WHERE (LEAST($LevelCap, spc.Level) <= $UserLevel OR spc.AssignedToUser = '" . $LoggedUser['ID'] . "')
	  AND spc.Status IN ('$Status')";

if ($ViewString == 'Your Unanswered') {
    if ($UserLevel >= $Classes[CONFIG['USER_CLASS']['MOD']]['Level']) {
        $WhereCondition .= " AND spc.Level >= " . $Classes[CONFIG['USER_CLASS']['MOD']]['Level'];
    } else if ($UserLevel >= $Classes[CONFIG['USER_CLASS']['FORUM_MOD']]['Level']) {
        $WhereCondition .= " AND spc.Level >= " . $Classes[CONFIG['USER_CLASS']['FORUM_MOD']]['Level'];
    }
}

list($Page, $Limit) = Format::page_limit(CONFIG['MESSAGES_PER_PAGE']);
// Get messages
$StaffPMs = $DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		spc.ID,
		spc.Subject,
		spc.UserID,
		spc.Status,
		spc.Level,
		spc.AssignedToUser,
		spc.Date,
		spc.Unread,
		COUNT(spm.ID) AS NumReplies,
		spc.ResolverID
	FROM staff_pm_conversations AS spc
	JOIN staff_pm_messages spm ON spm.ConvID = spc.ID
	$WhereCondition
	GROUP BY spc.ID
	ORDER BY $SortStr spc.Level DESC, spc.Date DESC
	LIMIT $Limit
");

$DB->query('SELECT FOUND_ROWS()');
list($NumResults) = $DB->next_record();
$DB->set_query_id($StaffPMs);

$CurURL = Format::get_url();
if (empty($CurURL)) {
    $CurURL = 'staffpm.php?';
} else {
    $CurURL = "staffpm.php?$CurURL&";
}
$Pages = Format::get_pages($Page, $NumResults, CONFIG['MESSAGES_PER_PAGE'], 9);

$Row = 'a';

// Start page
?>
<div class="LayoutBody">
    <div class="BodyHeader">
        <h2 class="BodyHeader-nav"><?= $ViewString ?><?= Lang::get('staffpm', 'space_staff_pms') ?></h2>
        <div class="BodyNavLinks">
            <? if ($IsStaff) { ?>
                <a href="staffpm.php" class="brackets"><?= Lang::get('staffpm', 'view_your_unanswered') ?></a>
            <?  } ?>
            <a href="staffpm.php?view=unanswered" class="brackets"><?= Lang::get('staffpm', 'view_all_unanswered') ?></a>
            <a href="staffpm.php?view=open" class="brackets"><?= Lang::get('staffpm', 'view_unresolved') ?></a>
            <a href="staffpm.php?view=resolved" class="brackets"><?= Lang::get('staffpm', 'view_resolved') ?></a>
            <? if ($IsStaff) { ?>
                <a href="staffpm.php?action=scoreboard" class="brackets"><?= Lang::get('staffpm', 'view_scoreboard') ?></a>
            <?  }

            if ($IsFLS && !$IsStaff) { ?>
                <span data-tooltip="This is the inbox where replies to Staff PMs you have sent are."><a href="staffpm.php?action=userinbox" class="brackets"><?= Lang::get('staffpm', 'personal_staff_inbox') ?></a></span>
            <?  } ?>
        </div>
    </div>
    <br />
    <br />
    <div class="BodyNavLinks">
        <?= $Pages ?>
    </div>
    <div class="BoxBody" id="inbox">
        <?

        if (!$DB->has_results()) {
            // No messages
        ?>
            <h2><?= Lang::get('staffpm', 'no_messages') ?></h2>
            <?

        } else {
            // Messages, draw table
            if ($ViewString != 'Resolved' && $IsStaff) {
                // Open multiresolve form
            ?>
                <form class="manage_form" name="staff_messages" method="post" action="staffpm.php" id="messageform">
                    <input type="hidden" name="action" value="multiresolve" />
                    <input type="hidden" name="view" value="<?= strtolower($View) ?>" />
                <?
            }

            // Table head
                ?>
                <div class="TableContainer">
                    <table class="Table TableUserInbox <?= ($ViewString != 'Resolved' && $IsStaff) ? ' checkboxes' : '' ?>">
                        <tr class="Table-rowHeader">
                            <? if ($ViewString != 'Resolved' && $IsStaff) { ?>
                                <td class="Table-cell" width="10"><input type="checkbox" onclick="toggleChecks('messageform', this);" /></td>
                            <?  } ?>
                            <td class="Table-cell" width="50%"><?= Lang::get('staffpm', 'subject') ?></td>
                            <td class="Table-cell"><?= Lang::get('staffpm', 'sender') ?></td>
                            <td class="Table-cell"><?= Lang::get('staffpm', 'date') ?></td>
                            <td class="Table-cell"><?= Lang::get('staffpm', 'assigned_to') ?></td>
                            <td class="Table-cell"><?= Lang::get('staffpm', 'replies') ?></td>
                            <? if ($ViewString == 'Resolved') { ?>
                                <td class="Table-cell"><?= Lang::get('staffpm', 'resolved_by') ?></td>
                            <?  } ?>
                        </tr>
                        <?

                        // List messages
                        while (list($ID, $Subject, $UserID, $Status, $Level, $AssignedToUser, $Date, $Unread, $NumReplies, $ResolverID) = $DB->next_record()) {
                            //$UserInfo = Users::user_info($UserID);
                            $UserStr = Users::format_username($UserID, true, true, true, true);

                            // Get assigned
                            if ($AssignedToUser == '') {
                                // Assigned to class
                                $Assigned = ($Level == 0) ? 'First Line Support' : $ClassLevels[$Level]['Name'];
                                // No + on Sysops
                                if ($Assigned != 'Sysop') {
                                    $Assigned .= '+';
                                }
                            } else {
                                // Assigned to user
                                // $UserInfo = Users::user_info($AssignedToUser);
                                $Assigned = Users::format_username($AssignedToUser, true, true, true, true);
                            }

                            // Get resolver
                            if ($ViewString == 'Resolved') {
                                //$UserInfo = Users::user_info($ResolverID);
                                $ResolverStr = Users::format_username($ResolverID, true, true, true, true);
                            }

                            // Table row
                        ?>
                            <tr class="Table-row">
                                <? if ($ViewString != 'Resolved' && $IsStaff) { ?>
                                    <td class="Table-cell Table-cellCenter"><input type="checkbox" name="id[]" value="<?= $ID ?>" /></td>
                                <?      } ?>
                                <td class="Table-cell"><a href="staffpm.php?action=viewconv&amp;id=<?= $ID ?>"><?= display_str($Subject) ?></a></td>
                                <td class="Table-cell"><?= $UserStr ?></td>
                                <td class="Table-cell"><?= time_diff($Date, 2, true) ?></td>
                                <td class="Table-cell"><?= $Assigned ?></td>
                                <td class="Table-cell"><?= $NumReplies - 1 ?></td>
                                <? if ($ViewString == 'Resolved') { ?>
                                    <td class="Table-cell"><?= $ResolverStr ?></td>
                                <?      } ?>
                            </tr>
                        <?

                            $DB->set_query_id($StaffPMs);
                        } //while

                        // Close table and multiresolve form
                        ?>
                    </table>
                </div>
                <? if ($ViewString != 'Resolved' && $IsStaff) { ?>
                    <div class="submit_div">
                        <input class="Button" type="submit" value="Resolve selected" />
                    </div>
                </form>
        <?
                }
            } //if (!$DB->has_results())
        ?>
    </div>
    <div class="BodyNavLinks">
        <?= $Pages ?>
    </div>
</div>
<?

View::show_footer();

?>