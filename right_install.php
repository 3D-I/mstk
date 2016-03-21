<?php
/***
* Usage:
* Download and unzip the file, upload it to your Board's root (i.e.: www.mydomain.com/phpBB3/)
* Point your browser to i.e.: www.mydomain.com/phpBB3/right_install.php) and follow instructions.
*
* @package - right_install.php 1.0.0-b3 (true versions comparison and more)
* @copyright (c) 2016 3Di (Marco T.) 16-Mar-2016
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
* Some code taken from modission_reset by Oyabun
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
// Start session management
$user->session_begin();
$auth->acl($user->data);
/* Vars */
$db_vers = $config['version'];
$version = PHPBB_VERSION;
$php_version = PHP_VERSION;
$styles_path = ($phpbb_root_path . 'styles');
$files = glob('styles/*/style.cfg');
/* If ANONYMOUS = login box */
if ((int) $user->data['user_id'] == ANONYMOUS)
{
	login_box(request_var('redirect', "right_install.$phpEx"));
}
if ((int) $user->data['user_type'] == USER_FOUNDER || $auth->acl_get('a_'))
{
	/* The party begins. If not correct versions tell them */
	if ((phpbb_version_compare(PHPBB_VERSION, $db_vers, "<>")) || (version_compare(PHP_VERSION, '5.3.3', '<')) || (version_compare(PHP_VERSION, '7.0.0', '>=')))
	{
		echo '<strong style="color:red">Versions mismatch:</strong><br />Your CONSTANTS file belongs to phpBB <font style="color:red">' . $version . '</font><br />Your DB says you are running phpBB <font style="color:red">' . $db_vers . '</font><br />Your PHP version says you are running PHP <font style="color:red">' . $php_version . '</font><br />';
	}
	else if ((phpbb_version_compare(PHPBB_VERSION, $db_vers, "=")) && (version_compare(PHP_VERSION, '5.3.3', '>')) && (version_compare(PHP_VERSION, '7.0.0', '<')))
	{
		echo '<strong style="color:green">Congratulations!</strong><br />Your CONSTANTS file belongs to phpBB <font style="color:green">' . $version . '</font><br />Your DB says you are running phpBB <font style="color:green">' . $db_vers . '</font><br />Your PHP version says you are running PHP <font style="color:green">' . $php_version . '</font><br />';
	}
	echo '<strong style="color:purple">The following stats are just for information purposes at the present time</strong><br />';
	/* List of available styles (version) */
	if (is_array($files))
	{
		foreach (array_slice(scandir($styles_path), 2) as $folder)
		{
			$style_names[] = substr($folder, 0);
		}
		foreach ($files as $file)
		{
			$content = file_get_contents($file);
			if (preg_match('/phpbb_version\s?=\s?(.+?)\s/', $content, $match) === 1)
			{
				if ((phpbb_version_compare($match[1], PHPBB_VERSION, "=")) && (phpbb_version_compare($match[1], $db_vers, "=")))
				{
					$match[1] = '<font style="color:green">' . $match[1] . '</font>';
				}
				else if ((phpbb_version_compare($match[1], PHPBB_VERSION, "<>")) || (phpbb_version_compare($match[1], $db_vers, "<>")))
				{
					$match[1] = '<font style="color:red">' . $match[1] . '</font>';
				}
				$style_phpbb_version[] = $match[1];
			}
		}
		$name_version_array = array_combine($style_names, $style_phpbb_version);
		foreach ($name_version_array as $key => $value)
		{
			$availables = '<font style="color:blue">' . $key . '</font>' . ' (' . $value . ')';
			$avail_ary[] = $availables;
		}
	}
	$availables = implode(', ', $avail_ary);
	echo 'Styles(version) available: ' . $availables;
	/* Pull everything from there */
	$default_style = ((int) $config['default_style']);
	$sql = 'SELECT *
		FROM ' . STYLES_TABLE . '
		GROUP BY style_id';
	$result = $db->sql_query($sql);
	while ($rows = $db->sql_fetchrow($result))
	{
		$style_path[] = $rows['style_path'];
		$styles_ids[] = $rows['style_id'];
		$names[$rows['style_path']] = $rows['style_path'];
	}
	$db->sql_freeresult($result);
	$name_id_ary = array_combine($styles_ids, $style_path);
	foreach ($name_id_ary as $key => $value)
	{
		$names_ids = $key . $value;
		$names_ids_ary[] = $names_ids;
	}
	$true_default = $style_path[array_search($default_style, array_keys($names_ids_ary)) - 1];
	echo '<br />Board\'s default style: <font style="color:purple">' . $true_default . '</font><br />';
	$styles_installed = implode(', ', $names);
	echo 'Styles installed: <font style="color:green">' . $styles_installed . '</font><br />';
	/* Return a list of styles from the DB, those in use by the Users and counts*/
	$sql = 'SELECT u.user_style, s.style_name, s.style_id, COUNT(u.user_style) AS style_count
		FROM ' . USERS_TABLE . ' u, ' . STYLES_TABLE . ' s
			WHERE u.user_style = s.style_id
		GROUP BY s.style_name';
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$style_count[$row['style_count']] = $row['style_name'];
		$count[$row['style_count']] = $row['style_count'];
	}
	$db->sql_freeresult($result);
	$name_count_array = array_combine($style_count, $count);
	foreach ($name_count_array as $key => $value)
	{
		$avail = $key . ' <font style="color:purple">(' . $value . ')</font>';
		$style_and_count[] = $avail;
	}
	$styles_in_use = implode(', ', $style_and_count);
	echo 'Styles in use (incl. bots): <font style="color:blue">' . $styles_in_use . '</font><br />';
	$en_lang_path = ($phpbb_root_path . 'language/en');
	if (file_exists($en_lang_path. "/common.$phpEx"))
	{
		echo '<font style="color:green">language/en/common.php file found.</font> Its version check is not yet available, though.';
	}
	else if (!file_exists($en_lang_path. "/common.$phpEx"))
	{
		echo '<font style="color:red">language/en/common.php file not found!</font> Critical error!';
	}
	/* cookies */
	$url = $request->server('SERVER_NAME', '');// REMOTE_ADDR // SERVER_NAME
	echo '<br /><font style="color:purple">Server name: </font><font style="color:blue">' . $url . '</font>';
	echo '<br /><font style="color:purple">Cookie domain: </font><font style="color:blue">' . $config['cookie_domain'] . '</font>';
	echo '<br /><font style="color:purple">Cookie name: </font><font style="color:blue">' . $config['cookie_name'] . '</font>';
	echo '<br /><font style="color:purple">Cookie path: </font><font style="color:blue">' . $config['cookie_path'] . '</font>';
	echo '<br /><font style="color:purple">Cookie secure: </font><font style="color:blue">' . $config['cookie_secure'] . '</font>';
	echo '<br /><font style="color:purple">DB info: </font><font style="color:blue">' . $db->sql_server_info() . '</font>';
	echo '<br /><font style="color:purple">ImageMagick path: </font><font style="color:blue">' . $config['img_imagick'] . '</font>';
	/* Let's check some folders' perms */
	$cache_dir = ($phpbb_root_path . 'cache');
	$perms = substr(sprintf('%o', fileperms($cache_dir)), -4);
	echo '<br /><font style="color:purple">Cache folder chmod permissions are set to: </font><font style="color:blue">' . $perms . '</font>';
	$store_dir = ($phpbb_root_path . 'store');
	$perms = substr(sprintf('%o', fileperms($store_dir)), -4);
	echo '<br /><font style="color:purple">Store folder chmod permissions are set to: </font><font style="color:blue">' . $perms . '</font>';
	$files_dir = ($phpbb_root_path . 'files');
	$perms = substr(sprintf('%o', fileperms($files_dir)), -4);
	echo '<br /><font style="color:purple">Files folder chmod permissions are set to: </font><font style="color:blue">' . $perms . '</font>';
	$av_up_dir = ($phpbb_root_path . 'images/avatars/upload');
	$perms = substr(sprintf('%o', fileperms($av_up_dir)), -4);
	echo '<br /><font style="color:purple">Images/avatar/upload folder chmod permissions are set to: </font><font style="color:blue">' . $perms . '</font>';
	/* Hasta la vista! */
	echo '<br /><font color="blue">Copy-paste these results or make a screenshot for further support...<br />...I am self destroying, hasta la vista!</font><br /><br />';
	echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="ZLN6KTV2WQSRN"><input type="image" src="https://www.paypal.com/en_GB/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal – The safer, easier way to pay online."><img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1"><font color="darkred"> Help the development of this Tool by a donation of your choice.</font></form>';
	/* uncomment the following line to turn on PHP info. */
	//phpinfo();
	/* comment out the following line to turn off the self-destroyer. */
	remove_me();
}
else
{
	/* If logged in without the right permissions, stop everything and self-destroy */
	trigger_error('You don\'t have permission to access the database and files. You need to be logged in as a founder or administrator.');
	remove_me();
}
/* Attempting to delete this file */
function remove_me()
{
	@unlink(__FILE__);
	/** Windows IIS servers may have a problem with unlinking recently created files.
	* * So check if file exists and give a message
	*/
	if (file_exists(__FILE__))
	{
		echo '<strong color="red">File could not be deleted.</strong> You will
		need to manually delete the ' . basename(__FILE__) . ' file from the server.';
	}
}
