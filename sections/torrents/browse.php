<?
include(CONFIG['SERVER_ROOT'] . '/sections/torrents/functions.php');
include(CONFIG['SERVER_ROOT'] . '/classes/torrenttable.class.php');

$headlink = new class implements SortLink {
    function link($SortKey, $DefaultWay = 'desc') {
        global $OrderBy, $OrderWay;
        if ($SortKey == $OrderBy) {
            if ($OrderWay == 'desc') {
                $NewWay = 'asc';
            } else {
                $NewWay = 'desc';
            }
        } else {
            $NewWay = $DefaultWay;
        }
        return "torrents.php?order_way=$NewWay&amp;order_by=$SortKey&amp;" . Format::get_url(array('order_way', 'order_by'));
    }
};

if (!empty($_GET['searchstr']) || !empty($_GET['groupname'])) {
    if (!empty($_GET['searchstr'])) {
        $SearchInfo = $_GET['searchstr'];
    } else {
        $SearchInfo = $_GET['groupname'];
    }

    // Search by infohash
    if ($InfoHash = is_valid_torrenthash($SearchInfo)) {
        $InfoHash = db_string(pack('H*', $InfoHash));
        $DB->query("
			SELECT ID, GroupID
			FROM torrents
			WHERE info_hash = '$InfoHash'");
        if ($DB->has_results()) {
            list($ID, $GroupID) = $DB->next_record();
            header("Location: torrents.php?id=$GroupID&torrentid=$ID");
            die();
        }
    } else if ($IMDBID = is_valid_imdbid($SearchInfo)) {
        $DB->query("
			SELECT ID
			FROM torrents_group
			WHERE IMDBID = '$IMDBID'");
        if ($DB->has_results()) {
            list($GroupID) = $DB->next_record();
            header("Location: torrents.php?id=$GroupID");
            die();
        }
    } else if (!empty($_GET['groupname'])) {
        $DB->query("
			SELECT ID
			FROM torrents_group
			WHERE Name = '" . db_string($SearchInfo) . "' or SubName = '" . db_string($SearchInfo) . "'");
        if ($DB->has_results()) {
            list($GroupID) = $DB->next_record();
            header("Location: torrents.php?id=$GroupID");
            die();
        }
    }
}

// Setting default search options
if (!empty($_GET['setdefault'])) {
    $UnsetList = array('page', 'setdefault');
    $UnsetRegexp = '/(&|^)(' . implode('|', $UnsetList) . ')=.*?(&|$)/i';

    $DB->query("
		SELECT SiteOptions
		FROM users_info
		WHERE UserID = '" . db_string($LoggedUser['ID']) . "'");
    list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
    $SiteOptions = unserialize_array($SiteOptions);
    $SiteOptions = array_merge(Users::default_site_options(), $SiteOptions);

    $SiteOptions['DefaultSearch'] = preg_replace($UnsetRegexp, '', $_SERVER['QUERY_STRING']);
    $DB->query("
		UPDATE users_info
		SET SiteOptions = '" . db_string(serialize($SiteOptions)) . "'
		WHERE UserID = '" . db_string($LoggedUser['ID']) . "'");
    $Cache->begin_transaction("user_info_heavy_$UserID");
    $Cache->update_row(false, array('DefaultSearch' => $SiteOptions['DefaultSearch']));
    $Cache->commit_transaction(0);

    // Clearing default search options
} elseif (!empty($_GET['cleardefault'])) {
    $DB->query("
		SELECT SiteOptions
		FROM users_info
		WHERE UserID = '" . db_string($LoggedUser['ID']) . "'");
    list($SiteOptions) = $DB->next_record(MYSQLI_NUM, false);
    $SiteOptions = unserialize_array($SiteOptions);
    $SiteOptions['DefaultSearch'] = '';
    $DB->query("
		UPDATE users_info
		SET SiteOptions = '" . db_string(serialize($SiteOptions)) . "'
		WHERE UserID = '" . db_string($LoggedUser['ID']) . "'");
    $Cache->begin_transaction("user_info_heavy_$UserID");
    $Cache->update_row(false, array('DefaultSearch' => ''));
    $Cache->commit_transaction(0);

    // Use default search options
} elseif (empty($_SERVER['QUERY_STRING']) || (count($_GET) === 1 && isset($_GET['page']))) {
    if (!empty($LoggedUser['DefaultSearch'])) {
        if (!empty($_GET['page'])) {
            $Page = $_GET['page'];
            parse_str($LoggedUser['DefaultSearch'], $_GET);
            $_GET['page'] = $Page;
        } else {
            parse_str($LoggedUser['DefaultSearch'], $_GET);
        }
    }
}
// Terms were not submitted via the search form
if (isset($_GET['searchsubmit'])) {
    $GroupResults = !empty($_GET['group_results']);
} else {
    $GroupResults = empty($LoggedUser['DisableGrouping2']);
}

if (!empty($_GET['order_way']) && $_GET['order_way'] == 'asc') {
    $OrderWay = 'asc';
} else {
    $OrderWay = 'desc';
}

if (empty($_GET['order_by']) || !isset(TorrentSearch::$SortOrders[$_GET['order_by']])) {
    $OrderBy = 'time'; // For header links
} else {
    $OrderBy = $_GET['order_by'];
}

$Page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
$Search = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $Page, CONFIG['TORRENTS_PER_PAGE']);
$Results = $Search->query($_GET);
$Groups = $Search->get_groups();

$RealNumResults = $NumResults = $Search->record_count();

if (check_perms('site_search_many')) {
    $LastPage = ceil($NumResults / CONFIG['TORRENTS_PER_PAGE']);
    $FixSearch = new TorrentSearch($GroupResults, $OrderBy, $OrderWay, $LastPage, CONFIG['TORRENTS_PER_PAGE']);
    $FixSearch->query($_GET);
    $RealNumResults = $NumResults = $FixSearch->record_count();
} else {
    $NumResults = min($NumResults, CONFIG['SPHINX_MAX_MATCHES']);
}

$HideFilter = isset($LoggedUser['ShowTorFilter']) && $LoggedUser['ShowTorFilter'] == 0;
// This is kinda ugly, but the enormous if paragraph was really hard to read
$AdvancedSearch = !empty($_GET['action']) && $_GET['action'] == 'advanced';
$AdvancedSearch |= !empty($LoggedUser['SearchType']) && (empty($_GET['action']) || $_GET['action'] == 'advanced');
$AdvancedSearch &= check_perms('site_advanced_search');
if ($AdvancedSearch) {
    $Action = 'action=advanced';
    $HideBasic = ' u-hidden';
    $HideAdvanced = '';
} else {
    $Action = 'action=basic';
    $HideBasic = '';
    $HideAdvanced = ' u-hidden';
}

View::show_header(Lang::get('torrents', 'header'), 'browse', 'PageTorrentHome');
//$TimeNow = new DateTime();
//$TimeUntil = new DateTime('2016-12-16 03:50:00');
//$Interval = $TimeUntil->diff($TimeNow);
//$Left = $Interval->format("%i MINS, %s SECONDS");
?>
<div class="LayoutBody">
    <form class="Form SearchPage Box SearchTorrent" name="torrents" method="get" action="" onsubmit="$(this).disableUnset();">
        <div class="SearchPageHeader">
            <div class="SearchPageHeader-title">
                <span class="is-basicText <?= $HideBasic ?>"><?= Lang::get('torrents', 'base') ?> /</span>
                <span class="is-basicLink <?= $HideAdvanced ?>"><a href="#" onclick="globalapp.toggleSearchTorrentAdvanced(event, 'basic')"><?= Lang::get('torrents', 'base') ?></a> /</span>
                <span class="is-advancedText <?= $HideAdvanced ?>"><?= Lang::get('torrents', 'advanced') ?></span>
                <span class="is-advancedLink <?= $HideBasic ?>"><a href="#" onclick="globalapp.toggleSearchTorrentAdvanced(event, 'advanced')"><?= Lang::get('torrents', 'advanced') ?></a></span>
                <?= Lang::get('torrents', 'search') ?>
                <a href="wiki.php?action=article&name=%E9%AB%98%E7%BA%A7%E6%90%9C%E7%B4%A2%E6%8C%87%E5%8D%97" target="_blank" data-tooltip="<?= Lang::get('torrents', 'guide_of_advanced_search') ?>">[?]</a>
            </div>
            <div class="SearchPageHeader-actions">
                <a href="#" onclick="globalapp.toggleAny(event, '.SearchPageBody', { updateText: true }); globalapp.toggleAny(event, '.SearchPageFooter', { updateText: true })" id="ft_toggle" class="brackets">
                    <span class="u-toggleAny-show <?= $HideFilter ?: 'u-hidden' ?>"><?= Lang::get('global', 'show') ?></span>
                    <span class="u-toggleAny-hide <?= $HideFilter ? 'u-hidden' : '' ?>"><?= Lang::get('global', 'hide') ?></span>
                </a>
            </div>
        </div>
        <div class="SearchPageBody <?= $HideFilter ? ' u-hidden' : '' ?>">
            <table class="Form-rowList">
                <tr class="Form-row is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('torrents', 'basic') ?>:</td>
                    <td class="Form-inputs is-splitEven">
                        <input class="is-movieName Input" type="text" spellcheck="false" size="40" name="groupname" placeholder="<?= Lang::get('global', 'movie_name_title') ?>" value="<? Format::form('groupname') ?>" />
                        <input class="is-artist Input" type="text" spellcheck="false" size="40" name="artistname" placeholder="<?= Lang::get('global', 'artist') ?>" value="<? Format::form('artistname') ?>" />
                        <input class="is-year Input" type="text" spellcheck="false" size="40" name="year" placeholder="<?= Lang::get('global', 'year') ?>" value="<? Format::form('year') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-languageRegion is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('global', 'language_region') ?>:</td>
                    <td class="Form-inputs is-splitEven">
                        <input class="Input" type="text" spellcheck="false" size="40" name="language" placeholder="<?= Lang::get('global', 'language') ?>" value="<? Format::form('language') ?>" />
                        <input class="Input" type="text" spellcheck="false" size="40" name="region" placeholder="<?= Lang::get('global', 'countries_and_regions') ?>" value="<? Format::form('region') ?>" />
                        <input class="Input" type="text" spellcheck="false" size="40" name="subtitles" placeholder="<?= Lang::get('global', 'subtitle') ?>" value="<? Format::form('subtitle') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-rating is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('global', 'rating') ?>:</td>
                    <td class="Form-inputs is-splitEven" colspan="3">
                        <input class="Input" type="text" spellcheck="false" size="40" name="imdbrating" placeholder="<?= Lang::get('global', 'imdb_rating') ?>" value="<? Format::form('imdbrating') ?>" />
                        <input class="Input" type="text" spellcheck="false" size="40" name="doubanrating" placeholder="<?= Lang::get('global', 'douban_rating') ?>" value="<? Format::form('doubanrating') ?>" />
                        <input class="Input" type="text" spellcheck="false" size="40" name="rtrating" placeholder="<?= Lang::get('global', 'rt_rating') ?>" value="<? Format::form('rtrating') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-editionInfo is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('global', 'edition_info') ?>:</td>
                    <td class="Form-inputs" colspan="3">
                        <input class="Input" type="text" spellcheck="false" size="40" name="remtitle" value="<? Format::form('remtitle') ?>" placeholder="<?= Lang::get('global', 'comma_separated_edition') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-fileList is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('torrents', 'ft_filelist') ?>:</td>
                    <td class="Form-inputs">
                        <input class="Input" type="text" spellcheck="false" size="40" name="filelist" value="<? Format::form('filelist') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-ripSpecifics is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('torrents', 'ft_ripspecifics') ?>:</td>
                    <td class="Form-inputs">
                        <select class="Input" id="source" name="source">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'source') ?></option>
                            <? foreach ($Sources as $SourceName) { ?>
                                <option class="Select-option" value="<?= display_str($SourceName); ?>" <? Format::selected('source', $SourceName) ?>><?= display_str($SourceName); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="codec">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'codec') ?></option>
                            <? foreach ($Codecs as $CodecName) { ?>
                                <option class="Select-option" value="<?= display_str($CodecName); ?>" <? Format::selected('codec', $CodecName) ?>><?= display_str($CodecName); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="container">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'container') ?></option>
                            <? foreach ($Containers as $ContainerName) { ?>
                                <option class="Select-option" value="<?= display_str($ContainerName); ?>" <? Format::selected('container', $ContainerName) ?>><?= display_str($ContainerName); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="resolution">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'resolution') ?></option>
                            <? foreach ($Resolutions as $ResolutionName) { ?>
                                <option class="Select-option" value="<?= display_str($ResolutionName); ?>" <? Format::selected('resolution', $ResolutionName) ?>><?= display_str($ResolutionName); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="processing">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'processing') ?></option>
                            <? foreach ($Processings as $ProcessingName) { ?>
                                <option class="Select-option" value="<?= display_str($ProcessingName); ?>" <? Format::selected('processing', $ProcessingName) ?>><?= display_str($ProcessingName); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="releasetype">
                            <option class="Select-option" value=""><?= Lang::get('torrents', 'ft_releasetype') ?></option>
                            <? foreach ($ReleaseTypes as $ID) { ?>
                                <option class="Select-option" value="<?= display_str($ID); ?>" <? Format::selected('releasetype', $ID) ?>><?= display_str(Lang::get('torrents', 'release_types')[$ID]); ?></option>
                            <?  } ?>
                        </select>
                        <select class="Input" name="freetorrent">
                            <option class="Select-option" value=""><?= Lang::get('tools', 'sales_promotion_plan') ?></option>
                            <option class="Select-option" value='1' <? $HideBasic ? Format::selected('freetorrent', '1') : '' ?>><?= Lang::get('tools', 'free_leech') ?></option>
                            <option class="Select-option" value='11' <? Format::selected('freetorrent', '11') ?>><?= Lang::get('tools', '25_percent_off') ?></option>
                            <option class="Select-option" value='12' <? Format::selected('freetorrent', '12') ?>><?= Lang::get('tools', '50_percent_off') ?></option>
                            <option class="Select-option" value='13' <? Format::selected('freetorrent', '13') ?>><?= Lang::get('tools', '75_percent_off') ?></option>
                            <option class="Select-option" value='2' <? Format::selected('freetorrent', '2') ?>><?= Lang::get('tools', 'neutral_leech') ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="Form-row is-special is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><?= Lang::get('torrents', 'feature') ?>:</td>
                    <td class="Form-inputs">
                        <div class="Checkbox">
                            <input class="Input" type="checkbox" value="1" name="chinesedubbed" <? $HideBasic ? Format::checked('chinesedubbed', 1) : '' ?> id="chinesedubbed" />
                            <label class="Radio-label" for="chinesedubbed"> <?= Lang::get('upload', 'chinese_dubbed_label') ?></label>
                        </div>
                        <div class="Checkbox">
                            <input class="Input" type="checkbox" value="1" name="specialsub" <? $HideBasic ? Format::checked('specialsub', 1) : '' ?> id="specialsub" />
                            <label class="Radio-label" for="specialsub"> <?= Lang::get('tools', 'special_effects_subtitles') ?></label>
                        </div>
                        <div class="Checkbox">
                            <input class="Input" type="checkbox" value="2" name="diy" <? $HideBasic ? Format::checked('diy', 2) : '' ?> id="self_rip" />
                            <label class="Radio-label" for="self_rip"> <?= Lang::get('upload', 'self_rip') ?></label>
                        </div>
                        <div class="Checkbox">
                            <input class="Input" type="checkbox" value="2" name="buy" <? $HideBasic ? Format::checked('buy', 2) : '' ?> id="buy" />
                            <label class="Radio-label" for="buy"> <?= Lang::get('torrents', 'buy') ?></label>
                        </div>


                    </td>

                </tr>

                <tr class="Form-row is-searchStr is-basic <?= $HideBasic ?>">
                    <td class="Form-label"><?= Lang::get('torrents', 'ftb_searchstr') ?>:</td>
                    <td class="Form-inputs ftb_searchstr">
                        <input class="Input" type="text" spellcheck="false" size="40" name="searchstr" value="<? Format::form('searchstr') ?>" />
                    </td>
                </tr>
                <tr class="Form-row is-tagFilter is-advanced <?= $HideAdvanced ?>">
                    <td class="Form-label"><span data-tooltip="<?= Lang::get('global', 'tags') ?>"><?= Lang::get('global', 'tags') ?>:</span></td>
                    <td class="Form-inputs">
                        <input class="Input" type="text" placeholder="<?= Lang::get('global', 'comma_separated') ?>" size="40" id="tags" name="taglist" value="<?= display_str($Search->get_terms('taglist')) ?>" <? Users::has_autocomplete_enabled('other'); ?> />
                        <div class="RadioGroup">
                            <div class="Radio">
                                <input class="Input" type="radio" name="tags_type" id="tags_type0" value="0" <? Format::selected('tags_type', 0, 'checked') ?> />
                                <label class="Radio-label" for="tags_type0"> <?= Lang::get('torrents', 'any') ?></label>
                            </div>
                            <div class="Radio">
                                <input class="Input" type="radio" name="tags_type" id="tags_type1" value="1" <? Format::selected('tags_type', 1, 'checked') ?> />
                                <label class="Radio-label" for="tags_type1"> <?= Lang::get('torrents', 'all') ?></label>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr class="Form-row is-order">
                    <td class="Form-label"><?= Lang::get('torrents', 'ft_order') ?>:</td>
                    <td class="Form-inputs">
                        <select class="Input" name="order_by">
                            <option class="Select-option" value="time" <? Format::selected('order_by', 'time') ?>><?= Lang::get('torrents', 'add_time') ?></option>
                            <option class="Select-option" value="year" <? Format::selected('order_by', 'year') ?>><?= Lang::get('torrents', 'year') ?></option>
                            <option class="Select-option" value="size" <? Format::selected('order_by', 'size') ?>><?= Lang::get('global', 'size') ?></option>
                            <option class="Select-option" value="snatched" <? Format::selected('order_by', 'snatched') ?>><?= Lang::get('global', 'snatched') ?></option>
                            <option class="Select-option" value="seeders" <? Format::selected('order_by', 'seeders') ?>><?= Lang::get('global', 'seeders') ?></option>
                            <option class="Select-option" value="leechers" <? Format::selected('order_by', 'leechers') ?>><?= Lang::get('global', 'leechers') ?></option>
                            <option class="Select-option" value="random" <? Format::selected('order_by', 'random') ?>><?= Lang::get('torrents', 'random') ?></option>
                        </select>
                        <select class="Input" name="order_way">
                            <option class="Select-option" value="desc" <? Format::selected('order_way', 'desc') ?>><?= Lang::get('torrents', 'desc') ?></option>
                            <option class="Select-option" value="asc" <? Format::selected('order_way', 'asc') ?>><?= Lang::get('torrents', 'asc') ?></option>
                        </select>
                    </td>
                </tr>
                <tr class="Form-row">
                    <td class="Form-label">
                    </td>
                    <td class="Form-inputs">
                        <div class="Checkbox is-freeTorrent <?= $HideBasic ?>">
                            <input class="Input" type="checkbox" value="1" name="freetorrent" <? $HideAdvanced ? Format::checked('freetorrent', '1') : '' ?> id="shows_free" />
                            <label for="shows_free"><?= Lang::get('torrents', 'only_shows_free_torrents') ?></label>
                        </div>
                        <div class="Checkbox is-groupResults">
                            <input class="Input" type="checkbox" value="1" name="group_results" id="group_results" <?= $GroupResults ? ' checked="checked"' : '' ?> />
                            <label for="group_results"><?= Lang::get('torrents', 'group_results') ?></label>
                        </div>
                    </td>
                </tr>
            </table>
            <table class="SearchTorrent-tagList <? if (empty($LoggedUser['ShowTags'])) { ?> hidden<? } ?>" id="taglist">
                <tr>
                    <?
                    $GenreTags = $Cache->get_value('genre_tags');
                    if (!$GenreTags) {
                        $DB->query('
		SELECT Name
		FROM tags
		WHERE TagType = \'genre\'
		ORDER BY Name');
                        $GenreTags = $DB->collect('Name');
                        $Cache->cache_value('genre_tags', $GenreTags, 3600 * 6);
                    }

                    $x = 0;
                    foreach ($GenreTags as $Tag) {
                    ?>
                        <td width="12.5%"><a href="#" onclick="globalapp.browseAddTag('<?= $Tag ?>'); return false;"><?= $Tag ?></a></td>
                        <?
                        $x++;
                        if ($x % 7 == 0) {
                        ?>
                </tr>
                <tr>
                <?
                        }
                    }
                    if ($x % 7 != 0) { // Padding
                ?>
                <td colspan="<?= (7 - ($x % 7)) ?>"> </td>
            <? } ?>
                </tr>
            </table>
        </div>
        <div class="SearchPageFooter <?= $HideFilter ? ' u-hidden' : '' ?>">
            <div class="SearchPageFooter-resultCount">
                <?= number_format($RealNumResults) ?> <?= Lang::get('torrents', 'space_results') ?>
                <?= !check_perms('site_search_many') ? "(" . Lang::get('torrents', 'showing_first_n_matches_before') . $NumResults . Lang::get('torrents', 'showing_first_n_matches_after') . ")" : "" ?>
            </div>
            <div class="SearchPageFooter-actions">
                <input class="Button" type="submit" value="<?= Lang::get('torrents', 'search_torrents') ?>" />
                <input class="is-inputAction" type="hidden" name="action" id="ft_type" value="<?= ($AdvancedSearch ? 'advanced' : 'basic') ?>" />
                <input type="hidden" name="searchsubmit" value="1" />
                <input class="Button" type="button" value="<?= Lang::get('torrents', 'reset') ?>" onclick="location.href = 'torrents.php<? if (isset($_GET['action']) && $_GET['action'] === 'advanced') { ?>?action=advanced<? } ?>'" />
                <? if ($Search->has_filters()) { ?>
                    <input class="Button" type="submit" name="setdefault" value="<?= Lang::get('torrents', 'setdefault') ?>" />
                <?
                }
                if (!empty($LoggedUser['DefaultSearch'])) {
                ?>
                    <input class="Button" type="submit" name="cleardefault" value="<?= Lang::get('torrents', 'cleardefault') ?>" />
                <?  } ?>
            </div>
        </div>
    </form>
    <?
    if ($NumResults == 0) {
        $text1 = Lang::get('torrents', 'search_empty_1');
        $text2 = Lang::get('torrents', 'search_empty_2');
        print <<<HTML
<div class="BoxBody" align="center">
	<h2>$text1</h2>
	<p>$text2</p>
</div>
</div>
HTML;
        View::show_footer();
        die();
    }

    if ($NumResults < ($Page - 1) * CONFIG['TORRENTS_PER_PAGE'] + 1) {
        $LastPage = ceil($NumResults / CONFIG['TORRENTS_PER_PAGE']);
        $Pages = Format::get_pages(0, $NumResults, CONFIG['TORRENTS_PER_PAGE']);
    ?>
        <div class="BoxBody" align="center">
            <h2>The requested page contains no matches.</h2>
            <p>You are requesting page <?= $Page ?>, but the search returned only <?= number_format($LastPage) ?> pages.</p>
        </div>
        <div class="BodyNavLinks">Go to page <?= $Pages ?></div>
</div>
<?
        View::show_footer();
        die();
    }

    // List of pages
    $Pages = Format::get_pages($Page, $NumResults, CONFIG['TORRENTS_PER_PAGE']);
?>

<div class="BodyNavLinks"><?= $Pages ?></div>
<?
if ($GroupResults || isset($GroupedCategories[$CategoryID - 1])) {
    $tableRender = new GroupTorrentTableView($Groups);
    $tableRender->with_check(true)->with_sort(true, $headlink)->with_year(true)->with_time(true)->render();
} else {
    $TorrentLists = [];
    foreach ($Results as $Key => $GroupID) {
        $TorrentLists[] = Torrents::convert_torrent($Groups[$GroupID], $Key);
    }
    $tableRender = new UngroupTorrentTableView($TorrentLists);
    $tableRender->with_sort(true, $headlink)->with_year(true)->with_time(true)->render();
}
?>
<div class="BodyNavLinks"><?= $Pages ?></div>
</div>
<?
View::show_footer();
