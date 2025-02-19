<?
/*
 * This page creates previews of all supported stylesheets
 * CONFIG['SERVER_ROOT'] . '/' . CONFIG['STATIC_SERVER'] . 'stylespreview' must exist and be writable
 * Dependencies are PhantomJS (http://phantomjs.org/) and
 * ImageMagick (http://www.imagemagick.org/script/index.php)
 */
View::show_header(Lang::get('tools', 'rerender_stylesheet_gallery_images'), '', 'PageToolRerenderGallery');
$DB->query('
	SELECT
		ID,
		LOWER(REPLACE(Name," ","_")) AS Name,
		Name AS ProperName
	FROM stylesheets');
$Styles = $DB->to_array('ID', MYSQLI_BOTH);
$ImagePath = CONFIG['SERVER_ROOT'] . '/' . CONFIG['STATIC_SERVER'] . 'stylespreview';
?>
<div class="LayoutBody">
    <h2><?= Lang::get('tools', 'rerender_stylesheet_gallery_images') ?></h2>
    <div class="LayoutMainSidebar">
        <div class="Sidebar LayoutMainSidebar-sidebar">
            <div class="SidebarItem Box">
                <div class="SidebarItem-header Box-header">
                    <?= Lang::get('tools', 'rendering_parameters') ?></div>
                <ul class="SidebarList SidebarItem-body Box-body">
                    <li class="SidebarList-item"><?= Lang::get('tools', 'server_root') ?>: <?= var_dump(CONFIG['SERVER_ROOT']); ?></li>
                    <li class="SidebarList-item"><?= Lang::get('tools', 'static_server') ?>: <?= var_dump(CONFIG['STATIC_SERVER']); ?></li>
                    <li class="SidebarList-item"><?= Lang::get('tools', 'whoami') ?>: <? echo (shell_exec('whoami')); ?></li>
                    <li class="SidebarList-item"><?= Lang::get('tools', 'path') ?>: <? echo dirname(__FILE__); ?></li>
                    <li class="SidebarList-item"><?= Lang::get('tools', 'nodejs') ?>: <? echo (shell_exec('node -v;')); ?></li>
                    <li class="SidebarList-item"><?= Lang::get('tools', 'puppeteer') ?>: <? echo (shell_exec('npm view -g puppeteer version')); ?></li>
                </ul>
            </div>
        </div>
        <div class="LayoutMainSidebar-main">
            <div class="box">
                <div class="head"><?= Lang::get('tools', 'about_rendering') ?></div>
                <div class="pad">
                    <?= Lang::get('tools', 'about_rendering_note') ?>

                </div>
            </div>
            <div class="box">
                <div class="head"><?= Lang::get('tools', 'rendering_status') ?></div>
                <div class="pad">
                    <?
                    //set_time_limit(0);
                    foreach ($Styles as $Style) {
                    ?>
                        <div class="box">
                            <h6><?= $Style['Name'] ?></h6>
                            <p><?= Lang::get('tools', 'build_preview') ?>:<br />
                                <?
                                $CmdLine = '/usr/bin/node "' . dirname(__FILE__) . '/render_build_preview.js" "' . CONFIG['SERVER_ROOT'] . '" "' . CONFIG['STATIC_SERVER'] . '" "' . $Style['Name'] . '" "' . dirname(__FILE__) . '" &';
                                echo $CmdLine . "<br />";
                                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                                $BuildResult = trim(shell_exec(escapeshellcmd($CmdLine)));
                                echo (empty($BuildResult)) ? 'Success.' : "Error occured: {$BuildResult}.";
                                ?>
                            </p>
                            <?
                            //If build was successful, snap a preview.
                            if (empty($BuildResult)) {
                            ?>
                                <p><?= Lang::get('tools', 'converting_image') ?>:
                                    <?
                                    $CmdLine = '/usr/bin/convert "' . $ImagePath . '/full_' . $Style['Name'] . '.png" -filter Box -resize 40% -quality 94 "' . $ImagePath . '/thumb_' . $Style['Name'] . '.png"';
                                    $ConvertResult = shell_exec(escapeshellcmd($CmdLine));
                                    echo (empty($ConvertResult)) ? 'Success.' : "Error occured: {$ConvertResult}.";
                                    ?>
                                </p>
                            <? } ?>
                        </div>
                    <? } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?
View::show_footer();
