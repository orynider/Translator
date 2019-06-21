<?php
/**
 *
 * Lnaguage Tools Extension for the phpBB Forum Software package
 *
* @copyright (c) orynider <http://mxpcms.sourceforge.net>
* @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
//namespace orynider\translator\acp;
$basename = basename( __FILE__);
$mx_root_path = './../../../';
$module_root_path = $mx_root_path . 'modules/translator/';
$admin_module_root_path = $module_root_path . 'admin/';

//$basename = basename( __FILE__);
//$mx_root_path = (defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? generate_board_url() . '/' : $phpbb_root_path;
//$module_root_path = $phpbb_root_path . 'ext/orynider/translator/';
//$admin_module_root_path = $module_root_path . 'acp/';

/* */
if ( !empty( $setmodules))
{	
	$module['Language_tools']['ACP_TRANSLATOR_CONFIG'] = mx_append_sid( $admin_module_root_path . $basename . '?mode=config');	
	$module['Language_tools']['ACP_TRANSLATE_MX_PORTAL'] = mx_append_sid( $admin_module_root_path . $basename . '?s=MXP&mode=translate');
	$module['Language_tools']['ACP_TRANSLATE_MX_MODULES'] = mx_append_sid( $admin_module_root_path . $basename . '?s=MODS&mode=translate');
	$module['Language_tools']['ACP_TRANSLATE_PHPBB_LANG'] = mx_append_sid( $admin_module_root_path . $basename . '?s=PHPBB&mode=translate');
	$module['Language_tools']['ACP_TRANSLATE_PHPBB_EXT'] = mx_append_sid( $admin_module_root_path . $basename . '?s=phpbb_ext&mode=translate');		
	return;
}
/* */

/**
* mx_langtools ACP module
 */		
$phpEx = substr( __FILE__, strrpos( __FILE__, '.') + 1);
define('MODULE_URL', PHPBB_URL . 'ext/orynider/translator/');		
define('IN_AJAX', (isset($_GET['ajax']) && ($_GET['ajax'] == 1) && ($_SERVER['HTTP_SEREFER'] = $_SERVER['PHP_SELF'])) ? 1 : 0);
define('IN_PORTAL', 1);
define('IN_ADMIN', 1);

$no_page_header = 'no_page_header';
require_once($mx_root_path . 'admin/pagestart.' . $phpEx);
//include_once($module_root_path . 'includes/translator.' . $phpEx);

//@error_reporting( E_ALL || !E_NOTICE);
//$translator = new translator();
/**
* Class  translator_module extends translator
* Displays a message to the user and allows him to send an email
*/
 
		
/* Get an instance of the admin controller */
if (!include_once($module_root_path . 'controller/translator.' . $phpEx))
{
	die('Cant find ' . $module_root_path . 'controller/translator.' . $phpEx);
}
		
//$translator = new orynider\translator\controller\translator();
$translator = new translator();
		
/* Requests */
//$action = $request->variable('action', '');
		
/* general vars */
$mode = $mx_request_vars->request('mode', 'generate');
$start = $mx_request_vars->request('start', 0); 
$s = $mx_request_vars->request('s', '');
$ajax = $mx_request_vars->request('ajax', 0);		
$set_file = $mx_request_vars->request('set_file', '');
$into = $mx_request_vars->request('into', '');
/* */
		
// Make the $u_action url available in the admin controller
//$translator->set_page_url($this->u_action);			

/** Load the "settings" or "manage" module modes **/
switch ($mode)
{
	case 'config':
		// Load a template from adm/style for our ACP page
		$tpl_name = 'acp_translator_config';
		// Set the page title for our ACP page
		$page_title = $lang['ACP_TRANSLATOR'];	
		// Load the display options handle in the admin controller $translator->display_settings($this->tpl_name, $this->page_title);
		//$this->display_settings($this->tpl_name, $this->page_title);				
	break;
	case 'translate':
	default:
		switch ($s)
		{	
			case 'MXP':
				// Load a template from adm/style for our ACP page
				$tpl_name = 'lang_translate';
				// Set the page title for our ACP page
				$page_title = $lang['ACP_TRANSLATE_MX_PORTAL'];
				// Load the display options handle in the admin controller $translator->display_translate($this->tpl_name, $this->page_title);
			break;			
			case 'MODS':
				// Load a template from adm/style for our ACP page
				$tpl_name = 'lang_translate';
				// Set the page title for our ACP page
				$page_title = $lang['ACP_TRANSLATE_MX_MODULES'];
				// Load the display options handle in the admin controller $translator->display_translate($this->tpl_name, $this->page_title);
			break;			
			case 'phpbb':
				// Load a template from adm/style for our ACP page
				$tpl_name = 'lang_translate';
				// Set the page title for our ACP page
				$page_title = $lang['ACP_TRANSLATE_PHPBB_LANG'];
				// Load the display options handle in the admin controller $translator->display_translate($this->tpl_name, $this->page_title);
			break;			
			case 'phpbb_ext':
				// Load a template from adm/style for our ACP page
				$tpl_name = 'lang_translate';
				// Set the page title for our ACP page
				$page_title = $lang['ACP_TRANSLATE_PHPBB_EXT'];
				// Load the display options handle in the admin controller $translator->display_translate($this->tpl_name, $this->page_title);
			break;
		}		
	break;	
			
}
		
if (IN_AJAX == 0)
{
	$lang['ENCODING'] = $translator->file_encoding;
	if ( isset( $_POST['save']) || isset( $_POST['download']) )
	{
		$translator->file_preparesave();
	}
	if ( isset( $_POST['save']) )
	{
		$translator->file_save();
	}
	else if ( isset( $_POST['download']) )
	{
		$translator->file_download();
	}

	require_once( $mx_root_path . 'admin/page_header_admin.' . $phpEx);
	$template->set_filenames(array('body' => $tpl_name.'.html'));
	$template->assign_block_vars('file_to_translate_select', array());
	
	$s_action = $admin_module_root_path . $basename;
	$params = $_SERVER['QUERY_STRING'];	
	
	if ( file_exists( $mx_root_path . TEMPLATE_ROOT_PATH . 'images/menu_icons/icon_info.gif') )
	{
		$img_info = PORTAL_URL . TEMPLATE_ROOT_PATH . 'images/menu_icons/icon_info.gif';
	}
	else
	{
		$img_info = PORTAL_URL . 'templates/_core/images/menu_icons/icon_info.gif';
	}
	
	$template->assign_vars(array( // #
		'TH_COLOR2' => $theme['th_color2'],
	
		'S_ACTION' => $s_action . '?' . str_replace( '&amp;', '&',$params),
		'S_ACTION_AJAX' => $s_action . '?' . str_replace( '&amp;', '&',$params) . '&ajax=1',
		'S_LANGUAGE_INTO' => $translator->gen_select_list( 'html', 'language', $translator->language_into, $translator->language_from),
		'S_MODULE_LIST' => $translator->gen_select_list( 'html', 'modules', $translator->module_select),
		'S_FILE_LIST' => $translator->gen_select_list( 'html', 'files', $translator->module_file),
		'L_RESET' => $lang['Reset'],
		'IMG_INFO' => $img_info,

		'I_LANGUAGE' => $translator->language_into,
		'I_MODULE' => $translator->module_select,
		'I_FILE' => $translator->module_file,	
	));

	$translator->assign_template_vars($template);
	
	$template->assign_vars( array( // #
		'L_MX_MODULES' => $lang['MX_Modules'],
	));
	
	if (($s == 'MODS') || ($s == 'phpbb_ext'))
	{
		$template->assign_block_vars('file_to_translate_select.modules', array());
		$template->assign_block_vars('modules', array());
	}
	
	$translator->file_translate();
	
	$template->pparse('body');
	require_once($mx_root_path . 'admin/page_footer_admin.' . $phpEx);


}
else
{ // AJAX
	$template->set_filenames( array('body' => 'selects.html'));
	
	$style = "width:100%;"; 
	if ($into == 'language')
	{
		$option_list = $translator->gen_select_list( 'html', 'language', $translator->language_into, $translator->language_from);
		$name = 'language[into]';
		$id = 'f_lang_into';
	}
	if ($into == 'files')
	{
		
		$option_list = $translator->gen_select_list( 'html', 'files', $translator->module_file);
		$name = 'translate[file]';
		$id = 'f_select_file';
	}

	$template->assign_block_vars('ajax_select', array(
		'NAME'		=> $name,
		'ID'		=> $id,
		'STYLE'		=> $style,
		'OPTIONS'	=> $option_list,
	));
	$template->pparse('body');
}
?>