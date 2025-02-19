<?php
authorize();

if (!check_perms('torrents_edit')) {
    error(403);
}
$ArtistID = $_POST['artistid'];
$Redirect = $_POST['redirect'];
$AliasName = Artists::normalise_artist_name($_POST['name']);
$DBAliasName = db_string($AliasName);
if (!$Redirect) {
    $Redirect = 0;
}

if (!is_number($ArtistID) || !($Redirect === 0 || is_number($Redirect)) || !$ArtistID) {
    error(0);
}

if ($AliasName == '') {
    error(Lang::get('artist', 'blank_artist_name'));
}

/*
 * In the case of foo, who released an album before changing his name to bar and releasing another
 * the field shared to make them appear on the same artist page is the ArtistID
 * 1. For a normal artist, there'll be one entry, with the ArtistID, the same name as the artist and a 0 redirect
 * 2. For Celine Dion (C�line Dion), there's two, same ArtistID, diff Names, one has a redirect to the alias of the first
 * 3. For foo, there's two, same ArtistID, diff names, no redirect
 */

$DB->query("
	SELECT AliasID, ArtistID, Name, Redirect
	FROM artists_alias
	WHERE Name = '$DBAliasName'");
if ($DB->has_results()) {
    while (list($CloneAliasID, $CloneArtistID, $CloneAliasName, $CloneRedirect) = $DB->next_record(MYSQLI_NUM, false)) {
        if (!strcasecmp($CloneAliasName, $AliasName)) {
            break;
        }
    }
    if ($CloneAliasID) {
        if ($ArtistID == $CloneArtistID && $Redirect == 0) {
            if ($CloneRedirect != 0) {
                $DB->query("
					UPDATE artists_alias
					SET ArtistID = '$ArtistID', Redirect = 0
					WHERE AliasID = '$CloneAliasID'");
                Misc::write_log("Redirection for the alias $CloneAliasID ($DBAliasName) for the artist $ArtistID was removed by user $LoggedUser[ID] ($LoggedUser[Username])");
            } else {
                error(Lang::get('artist', 'no_changes_were_made'));
            }
        } else {
            error(Lang::get('artist', 'an_alias_already_exists_before') . $CloneArtistID . Lang::get('artist', 'an_alias_already_exists_after'));
        }
    }
}
if (!$CloneAliasID) {
    if ($Redirect) {
        $DB->query("
			SELECT ArtistID, Redirect
			FROM artists_alias
			WHERE AliasID = $Redirect");
        if (!$DB->has_results()) {
            error(Lang::get('artist', 'cannot_redirect'));
        }
        list($FoundArtistID, $FoundRedirect) = $DB->next_record();
        if ($ArtistID != $FoundArtistID) {
            error(Lang::get('artist', 'redirection_must_target'));
        }
        if ($FoundRedirect != 0) {
            $Redirect = $FoundRedirect;
        }
    }
    $DB->query("
		INSERT INTO artists_alias
			(ArtistID, Name, Redirect, UserID)
		VALUES
			($ArtistID, '$DBAliasName', $Redirect, " . $LoggedUser['ID'] . ')');
    $AliasID = $DB->inserted_id();

    $DB->query("
		SELECT Name
		FROM artists_group
		WHERE ArtistID = $ArtistID");
    list($ArtistName) = $DB->next_record(MYSQLI_NUM, false);

    Misc::write_log("The alias $AliasID ($DBAliasName) was added to the artist $ArtistID (" . db_string($ArtistName) . ') by user ' . $LoggedUser['ID'] . ' (' . $LoggedUser['Username'] . ')');
}

$Location = (empty($_SERVER['HTTP_REFERER'])) ? "artist.php?action=edit&artistid={$ArtistID}" : $_SERVER['HTTP_REFERER'];
header("Location: {$Location}");
