<?
View::show_header(Lang::get('login', 'disabled'), '', 'PageLoginDisabled');
if (isset($_POST['email']) && FEATURE_EMAIL_REENABLE) {
    // Handle auto-enable request
    if ($_POST['email'] != '') {
        $Output = AutoEnable::new_request(db_string($_POST['username']), db_string($_POST['email']));
    } else {
        $Output = Lang::get('login', 'enter_valid_email');
    }

    $Output .= "<br /><br /><a href='login.php?action=disabled'>" . Lang::get('login', 'back') . "</a>";
}
if ((empty($_POST['submit']) || empty($_POST['username'])) && !isset($Output)) {
?>
    <p class="u-colorWarning">
        <?= Lang::get('login', 'disabled_note1') ?>
        <? if (FEATURE_EMAIL_REENABLE) { ?>
            <?= Lang::get('login', 'disabled_note2') ?>
    <form action="" method="POST">
        <input class="Input" type="email" placeholder="<?= Lang::get('login', 'email_address_placeholder') ?>" name="email" required />
        <input class="Button" type="submit" value="<?= Lang::get('global', 'submit') ?>" />
        <input type="hidden" name="username" value="<?= $_COOKIE['username'] ?>" />
    </form><br />
<? } ?>
<?= Lang::get('login', 'disabled_note3') ?>
<br />
<br />
</p>



<script type="text/javascript">
    function toggle_visibility(id) {
        var e = document.getElementById(id);
        if (e.style.display === 'block') {
            e.style.display = 'none';
        } else {
            e.style.display = 'block';
        }
    }
</script>

<div id="golden_rules" class="HtmlText rule_summary" style="width: 35%; font-weight: bold; display: none; text-align: left;">
    <? Rules::display_golden_rules(); ?>
    <br /><br />
</div>

<?
} else if (!isset($Output)) {
    $Nick = $_POST['username'];
    $Nick = preg_replace('/[^a-zA-Z0-9\[\]\\`\^\{\}\|_]/', '', $Nick);
    if (strlen($Nick) == 0) {
        $Nick = CONFIG['SITE_NAME'] . 'Guest????';
    } else {
        if (is_numeric(substr($Nick, 0, 1))) {
            $Nick = '_' . $Nick;
        }
    }
?>
    <div class="LayoutBody">
        <div class="header">
            <h3 id="general">Disabled IRC</h3>
        </div>
        <div class="Box">
            <div class="Box-body HtmlText">
                <div style="padding: 0px 10px 10px 20px;">
                    <p>Please read the topic carefully.</p>
                </div>
                <applet codebase="static/irc/" code="IRCApplet.class" archive="irc.jar,sbox.jar" width="800" height="600" align="center">
                    <param name="nick" value="<?= ($Nick) ?>" />
                    <param name="alternatenick" value="<?= CONFIG['SITE_NAME'] ?>Guest????" />
                    <param name="name" value="Java IRC User" />
                    <param name="host" value="<?= (CONFIG['BOT_SERVER']) ?>" />
                    <param name="multiserver" value="false" />
                    <param name="autorejoin" value="false" />
                    <param name="command1" value="JOIN <?= CONFIG['BOT_DISABLED_CHAN'] ?>" />
                    <param name="gui" value="sbox" />
                    <param name="pixx:highlight" value="true" />
                    <param name="pixx:highlightnick" value="true" />
                    <param name="pixx:prefixops" value="true" />
                    <param name="sbox:scrollspeed" value="5" />
                </applet>
            </div>
        </div>
    </div>
<?
} else {
    echo $Output;
}

View::show_footer();
?>