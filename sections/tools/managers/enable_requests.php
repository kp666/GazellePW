<?

if (!check_perms('users_mod')) {
    error(403);
}

if (!FEATURE_EMAIL_REENABLE) {
    // This feature is disabled
    header("Location: tools.php");
    die();
}

View::show_header(Lang::get('tools', 'enable_requests'), 'enable_requests');

// Pagination
$RequestsPerPage = 25;
list($Page, $Limit) = Format::page_limit($RequestsPerPage);

// How can things be ordered?
$OrderBys = array(
    'submitted_timestamp' => 'uer.Timestamp',
    'outcome' => 'uer.Outcome',
    'handled_timestamp' => 'uer.HandledTimestamp'
);

$Where = [];
$Joins = [];

// Default orderings
$OrderBy = "uer.Timestamp";
$OrderWay = "DESC";

// Build query for different views
if ($_GET['view'] == 'perfect') {
    $Where[] = "um.Email = uer.Email";
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "uer.IP = (SELECT IP FROM users_history_ips uhi1 WHERE uhi1.StartTime = (SELECT MAX(StartTime) FROM users_history_ips uhi2 WHERE uhi2.UserID = uer.UserID ORDER BY StartTime DESC LIMIT 1))";
    $Where[] = "(SELECT 1 FROM users_history_ips uhi WHERE uhi.IP = uer.IP AND uhi.UserID != uer.UserID) IS NULL";
    $Where[] = "ui.BanReason = '3'";
} else if ($_GET['view'] == 'minus_ip') {
    $Where[] = "um.Email = uer.Email";
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "ui.BanReason = '3'";
} else if ($_GET['view'] == 'invalid_email') {
    $Joins[] = "JOIN users_main um ON um.ID = uer.UserID";
    $Where[] = "um.Email != uer.Email";
} else if ($_GET['view'] == 'ip_overlap') {
    $Joins[] = "JOIN users_history_ips uhi ON uhi.IP = uer.IP AND uhi.UserID != uer.UserID";
} else if ($_GET['view'] == 'manual_disable') {
    $Where[] = "ui.BanReason != '3'";
} else {
    $Joins[] = '';
}
// End views

// Build query further based on search
if (isset($_GET['search'])) {
    $Username = db_string($_GET['username']);
    $IP = db_string($_GET['ip']);
    $SubmittedBetween = db_string($_GET['submitted_between']);
    $SubmittedTimestamp1 = db_string($_GET['submitted_timestamp1']);
    $SubmittedTimestamp2 = db_string($_GET['submitted_timestamp2']);
    $HandledUsername = db_string($_GET['handled_username']);
    $HandledBetween = db_string($_GET['handled_between']);
    $HandledTimestamp1 = db_string($_GET['handled_timestamp1']);
    $HandledTimestamp2 = db_string($_GET['handled_timestamp2']);
    $OutcomeSearch = (int) $_GET['outcome_search'];
    $Checked = (isset($_GET['show_checked']));

    if (array_key_exists($_GET['order'], $OrderBys)) {
        $OrderBy = $OrderBys[$_GET['order']];
    }

    if ($_GET['way'] == "asc" || $_GET['way'] == "desc") {
        $OrderWay = $_GET['way'];
    }

    if (!empty($Username)) {
        $Joins[] = "JOIN users_main um1 ON um1.ID = uer.UserID";
    }

    if (!empty($HandledUsername)) {
        $Joins[] = "JOIN users_main um2 ON um2.ID = uer.CheckedBy";
    }

    $Where = array_merge($Where, AutoEnable::build_search_query(
        $Username,
        $IP,
        $SubmittedBetween,
        $SubmittedTimestamp1,
        $SubmittedTimestamp2,
        $HandledUsername,
        $HandledBetween,
        $HandledTimestamp1,
        $HandledTimestamp2,
        $OutcomeSearch,
        $Checked
    ));
}
// End search queries

$ShowChecked = $Checked || !empty($HandledUsername) || !empty($HandledTimestamp1) || !empty($OutcomeSearch);

if (!$ShowChecked || count($Where) == 0) {
    // If no search is entered, add this to the query to only show unchecked requests
    $Where[] = 'Outcome IS NULL';
}

$QueryID = $DB->query("
    SELECT SQL_CALC_FOUND_ROWS
           uer.ID,
           uer.UserID,
           uer.Email,
           uer.IP,
           uer.UserAgent,
           uer.Timestamp,
           ui.BanReason,
           uer.CheckedBy,
           uer.HandledTimestamp,
           uer.Outcome
    FROM users_enable_requests AS uer
    JOIN users_info ui ON ui.UserID = uer.UserID
    " . implode(' ', $Joins) . "
    WHERE
    " . implode(' AND ', $Where) . "
    ORDER BY $OrderBy $OrderWay
    LIMIT $Limit");

$DB->query("SELECT FOUND_ROWS()");
list($NumResults) = $DB->next_record();
$DB->set_query_id($QueryID);
?>

<div class="BodyHeader">
    <h2 class="BodyHeader-nav"><?= Lang::get('tools', 'auto_enable_requests') ?></h2>
</div>
<div class="BodyNavLinks">
    <a class="brackets" href="tools.php?action=enable_requests" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_main_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_main') ?></a>
    <a class="brackets" href="tools.php?action=enable_requests&amp;view=perfect&amp;<?= Format::get_url(array('view', 'action')) ?>" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_perfect_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_perfect') ?></a>
    <a class="brackets" href="tools.php?action=enable_requests&amp;view=minus_ip&amp;<?= Format::get_url(array('view', 'action')) ?>" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_perfect_minus_ip_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_perfect_minus_ip') ?></a>
    <a class="brackets" href="tools.php?action=enable_requests&amp;view=invalid_email&amp;<?= Format::get_url(array('view', 'action')) ?>" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_invalid_email_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_invalid_email') ?></a>
    <a class="brackets" href="tools.php?action=enable_requests&amp;view=ip_overlap&amp;<?= Format::get_url(array('view', 'action')) ?>" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_ip_overlap_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_ip_overlap') ?></a>
    <a class="brackets" href="tools.php?action=enable_requests&amp;view=manual_disable&amp;<?= Format::get_url(array('view', 'action')) ?>" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_manual_disable_title') ?>"><?= Lang::get('tools', 'auto_enable_requests_manual_disable') ?></a>
    <a class="brackets" href="" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_search_title') ?>" onclick="$('#search_form').gtoggle(); return false;"><?= Lang::get('tools', 'auto_enable_requests_search') ?></a>
    <a class="brackets" href="" data-tooltip="<?= Lang::get('tools', 'auto_enable_requests_scores_title') ?>" onclick="$('#scores').gtoggle(); return false;"><?= Lang::get('tools', 'auto_enable_requests_scores') ?></a>
</div><br />
<div class="LayoutBody">
    <div class="TableContainer">
        <table class="Table hidden" id="scores" style="width: 50%; margin: 0 auto;">
            <tr class="Table-rowHeader">
                <th class="Table-cell"><?= Lang::get('tools', 'username') ?></th>
                <th class="Table-cell"><?= Lang::get('tools', 'auto_enable_requests_checked') ?></th>
            </tr>
            <? $DB->query("
        SELECT COUNT(CheckedBy), CheckedBy
        FROM users_enable_requests
        WHERE CheckedBy IS NOT NULL
        GROUP BY CheckedBy
        ORDER BY COUNT(CheckedBy) DESC
        LIMIT 50");
            while (list($Checked, $UserID) = $DB->next_record()) { ?>
                <tr class="Table-row">
                    <td class="Table-cell Table-cellCenter"><?= Users::format_username($UserID) ?></td>
                    <td class="Table-cell Table-cellCenter"><?= $Checked ?></td>
                </tr>
            <?     }
            $DB->set_query_id($QueryID); ?>
        </table>
    </div>
    <form class="Form SearchPage Box SearchEnableRequest <?= !isset($_GET['search']) ? 'hidden' : '' ?>" action="" method="GET" id="search_form">
        <input type="hidden" name="action" value="enable_requests" />
        <input type="hidden" name="view" value="<?= $_GET['view'] ?>" />
        <input type="hidden" name="search" value="1" />
        <table class="Form-rowList">
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'username') ?></td>
                <td class="Form-inputs"><input class="Input" type="text" name="username" value="<?= $_GET['username'] ?>" /></td>
            </tr>
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'ip_address') ?></td>
                <td class="Form-inputs"><input class="Input" type="text" name="ip" value="<?= $_GET['ip'] ?>" /></td>
            </tr>
            <tr class="Form-row">
                <td class="label" data-tooltip="<?= Lang::get('tools', 'submitted_timestamp_title') ?>"><?= Lang::get('tools', 'submitted_timestamp') ?></td>
                <td class="Form-inputs">
                    <select class="Input" name="submitted_between" onchange="ChangeDateSearch(this.value, 'submitted_timestamp2');">
                        <option class="Select-option" value="on" <?= $_GET['submitted_between'] == 'on' ? 'selected' : '' ?>><?= Lang::get('tools', 'submitted_on') ?></option>
                        <option class="Select-option" value="before" <?= $_GET['submitted_between'] == 'before' ? 'selected' : '' ?>><?= Lang::get('tools', 'submitted_before') ?></option>
                        <option class="Select-option" value="after" <?= $_GET['submitted_between'] == 'after' ? 'selected' : '' ?>><?= Lang::get('tools', 'submitted_after') ?></option>
                        <option class="Select-option" value="between" <?= $_GET['submitted_between'] == 'between' ? 'selected' : '' ?>><?= Lang::get('tools', 'submitted_between') ?></option>
                    </select>
                    <input class="Input" type="date" name="submitted_timestamp1" value="<?= $_GET['submitted_timestamp1'] ?>" />
                    <input class="Input" type="date" id="submitted_timestamp2" name="submitted_timestamp2" value="<?= $_GET['submitted_timestamp2'] ?>" <?= $_GET['submitted_between'] != 'between' ? 'style="display: none;"' : '' ?> />
                </td>
            </tr>
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'handled_by_username') ?></td>
                <td class="Form-inputs"><input class="Input" type="text" name="handled_username" value="<?= $_GET['handled_username'] ?>" /></td>
            </tr>
            <tr class="Form-row">
                <td class="label" data-tooltip="<?= Lang::get('tools', 'handled_timestamp_title') ?>"><?= Lang::get('tools', 'handled_timestamp') ?></td>
                <td class="Form-inputs">
                    <select class="Input" name="handled_between" onchange="ChangeDateSearch(this.value, 'handled_timestamp2');">
                        <option class="Select-option" value="on" <?= $_GET['handled_between'] == 'on' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_on') ?></option>
                        <option class="Select-option" value="before" <?= $_GET['handled_between'] == 'before' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_before') ?></option>
                        <option class="Select-option" value="after" <?= $_GET['handled_between'] == 'after' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_after') ?></option>
                        <option class="Select-option" value="between" <?= $_GET['handled_between'] == 'between' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_between') ?></option>
                    </select>
                    <input class="Input" type="date" name="handled_timestamp1" value="<?= $_GET['handled_timestamp1'] ?>" />
                    <input class="Input" type="date" id="handled_timestamp2" name="handled_timestamp2" value="<?= $_GET['handled_timestamp2'] ?>" <?= $_GET['handled_between'] != 'between' ? 'style="display: none;"' : '' ?> />
                </td>
            </tr>
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'handled_outcome') ?></td>
                <td class="Form-inputs">
                    <select class="Input" name="outcome_search">
                        <option class="Select-option" value="">---</option>
                        <option class="Select-option" value="<?= AutoEnable::APPROVED ?>" <?= $_GET['outcome_search'] == AutoEnable::APPROVED ? 'selected' : '' ?>><?= Lang::get('tools', 'outcome_approved') ?></option>
                        <option class="Select-option" value="<?= AutoEnable::DENIED ?>" <?= $_GET['outcome_search'] == AutoEnable::DENIED ? 'selected' : '' ?>><?= Lang::get('tools', 'outcome_denied') ?></option>
                        <option class="Select-option" value="<?= AutoEnable::DISCARDED ?>" <?= $_GET['outcome_search'] == AutoEnable::DISCARDED ? 'selected' : '' ?>><?= Lang::get('tools', 'outcome_discarded') ?></option>
                    </select>
                </td>
            </tr>
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'include_checked') ?></td>
                <td class="Form-inputs">
                    <input class="Input" type="checkbox" name="show_checked" <?= isset($_GET['show_checked']) ? Lang::get('tools', 'checked_mark') : '' ?> />
                </td>
            </tr>
            <tr class="Form-row">
                <td class="label"><?= Lang::get('tools', 'order_by') ?></td>
                <td class="Form-inputs">
                    <select class="Input" name="order">
                        <option class="Select-option" value="submitted_timestamp" <?= $_GET['order'] == 'submitted_timestamp' ? 'selected' : '' ?>><?= Lang::get('tools', 'submitted_timestamp') ?></option>
                        <option class="Select-option" value="outcome" <?= $_GET['order'] == 'outcome' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_outcome') ?></option>
                        <option class="Select-option" value="handled_timestamp" <?= $_GET['order'] == 'handled_timestamp' ? 'selected' : '' ?>><?= Lang::get('tools', 'handled_timestamp') ?></option>
                    </select>
                    <select class="Input" name="way">
                        <option class="Select-option" value="asc" <?= $_GET['way'] == 'asc' ? 'selected' : '' ?>><?= Lang::get('tools', 'ascending') ?></option>
                        <option class="Select-option" value="desc" <?= !isset($_GET['way']) || $_GET['way'] == 'desc' ? 'selected' : '' ?>><?= Lang::get('tools', 'descending') ?></option>
                    </select>
                </td>
            </tr>
            <tr class="Form-row">
                <td class="Form-submit" colspan=2>
                    <input class="Button" type="submit" value="<?= Lang::get('global', 'search') ?>" />
                </td>
            </tr>
        </table>
    </form>
    <?
    if ($NumResults > 0) { ?>
        <div class="BodyNavLinks">
            <?
            $Pages = Format::get_pages($Page, $NumResults, $RequestsPerPage);
            echo $Pages;
            ?>
        </div>
        <div class='option'>
            <input class=" Button" type="submit" id="outcome" value="Approve Selected" />
            <input class="Button" type="submit" id="outcome" value="Reject Selected" />
            <input class="Button" type="submit" id="outcome" value="Discard Selected" />
        </div>
        <div class="TableContainer">
            <table class="TableEnableRequest Table">
                <tr class="Table-rowHeader">
                    <td class="Table-cell Table-cellCenter">
                        <input class="Input" type="checkbox" id="check_all" />
                    </td>
                    <td class="Table-cell"><?= Lang::get('tools', 'username') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'email_address') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'ip_address') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'user_agent') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'age') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'ban_reason') ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'comment') ?><?= $ShowChecked ? Lang::get('tools', 'comment_checked_by') : '' ?></td>
                    <td class="Table-cell"><?= Lang::get('tools', 'submit') ?><?= $ShowChecked ? Lang::get('tools', 'submit_checked_date') : '' ?></td>
                    <? if ($ShowChecked) { ?>
                        <td class="Table-cell"><?= Lang::get('tools', 'outcome') ?></td>
                    <?      } ?>
                </tr>
                <?
                while (list($ID, $UserID, $Email, $IP, $UserAgent, $Timestamp, $BanReason, $CheckedBy, $HandledTimestamp, $Outcome) = $DB->next_record()) {
                ?>
                    <tr class="Table-row" id="row_<?= $ID ?>">
                        <td class="Table-cell Table-celLcenter">
                            <? if (!$HandledTimestamp) { ?>
                                <input class="Input" type="checkbox" id="multi" data-id="<?= $ID ?>" />
                            <?          } ?>
                        </td>
                        <td class="Table-cell"><?= Users::format_username($UserID) ?></td>
                        <td class="Table-cell"><?= display_str($Email) ?></td>

                        <td class="Table-cell"><?= display_str($IP) ?></td>

                        <td class="Table-cell"><?= display_str($UserAgent) ?></td>
                        <td class="Table-cell"><?= time_diff($Timestamp) ?></td>
                        <td class="Table-cell"><?= ($BanReason == 3) ? '<b>' . Lang::get('tools', 'inactivity') . '</b>' : Lang::get('tools', 'other') ?></td>
                        <? if (!$HandledTimestamp) { ?>
                            <td class="Table-cell"><input class="Input" type="text" id="comment<?= $ID ?>" placeholder="<?= Lang::get('tools', 'comment') ?>" /></td>
                            <td class="Table-cell">
                                <div class='option'>
                                    <input class="Button" type="submit" id="outcome" value="Approve" data-id="<?= $ID ?>" />
                                    <input class="Button" type="submit" id="outcome" value="Reject" data-id="<?= $ID ?>" />
                                    <input class="Button" type="submit" id="outcome" value="Discard" data-id="<?= $ID ?>" />
                                </div>
                            </td>
                        <?      } else { ?>
                            <td class="Table-cell"><?= Users::format_username($CheckedBy); ?></td>
                            <td class="Table-cell"><?= $HandledTimestamp ?></td>
                        <?      }

                        if ($ShowChecked) { ?>
                            <td class="Table-cell"><?= AutoEnable::get_outcome_string($Outcome) ?>
                                <? if ($Outcome == AutoEnable::DISCARDED) { ?>
                                    <a href="" id="unresolve" onclick="return false;" class="brackets" data-id="<?= $ID ?>"><?= Lang::get('tools', 'unresolve') ?></a>
                                <?          } ?>
                            </td>
                        <?      } ?>
                    </tr>
                <?
                }
                ?>
            </table>
        </div>
        <div class="BodyNavLinks">
            <?
            $Pages = Format::get_pages($Page, $NumResults, $RequestsPerPage);
            echo $Pages;
            ?>
        </div>

    <? } else { ?>
        <h2 align="center"><?= Lang::get('tools', 'no_new_pending_auto_enable_requests') ?><?= isset($_GET['view']) ? Lang::get('tools', 'space_in_this_view') : '' ?></h2>
    <? }
    ?>
</div>
<?
View::show_footer();
