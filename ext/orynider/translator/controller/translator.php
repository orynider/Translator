<?php
/**
 *
 * Lnaguage Tools Extension for the phpBB Forum Software package
 * @author culprit_cz
* @copyright (c) orynider <http://mxpcms.sourceforge.net>
* @license GNU General Public License, version 2 (GPL-2.0)
 *
 */
namespace orynider\translator\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use orynider\translator\google_translater;

/**
 * translator
 * 
 * @package Translator
 * @author culprit_cz
 * @copyright Copyright (c) 2008
 * @version $Id: translator.php,v 1.5 2008/02/29 15:36:48 orynider Exp $
 * @access public
 */
class translator extends \orynider\translator\core\translator
{
	var $page_title;
	var $tpl_name;
	var $u_action;
	var $parent_id = 0;	
	/** @var \phpbb\cache\driver\driver_interface */
	protected $cache;
	/** @var \phpbb\config\config */
	protected $config;
	/** @var ContainerInterface */
	protected $container;
	/** @var \phpbb\controller\helper */
	protected $helper;
	/** @var \phpbb\db\driver\driver_interface */
	protected $db; 
	/** @var \phpbb\log\log */
	protected $log;
	/** @var \phpbb\request\request 
	        $_GET = $this->request->query->all();
	        $_POST = $this->request->request->all();
	        $_SERVER = $this->request->server->all();
	        $_COOKIE = $this->request->cookies->all();
	**/	
	protected $request;
	/** @var \phpbb\template\template */
	protected $template;
	/** @var \phpbb\user */
	protected $user;
	/** @var string phpBB root path */
	protected $root_path;
	
	protected $common_language_files_loaded;
	
	protected $phpbb_admin_path;
	/** @var string forum root path */
	protected $forum_root_path;
	/** @var string portal_backend */	
	var $backend;
	/** @var string */
	protected $table_prefix;	
	/** @var string phpEx */
	protected $php_ext;
    /**
	* Server and execution environment parameters ($_SERVER).
	*
	* @var \Symfony\Component\HttpFoundation\ServerBag
	*/
	public $get;
	public $post;	
	public $server;	
	public $cookie;
	
	var $language_list = array();
	var $forum_language_list = array();	
	var $module_list = array();
	var $language_file_list = array();
	/** @var \phpbb\language\language */
	var $lang = array();

	var $orig_ary = array();
	var $tran_ary = array();
	var $g_ary = array();
	var $language_from = '';
	var $langauge_into = '';
	var $module_select = '';
	var $module_file   = '';
	
	var $file_encoding = 'UTF-8';
	
	var $file_save_path = '';
	var $file_save_content = '';
	/**
	 * Constructor
	 *
	 * @param \phpbb\cache\driver\driver_interface  $cache
	 * @param \phpbb\config\config                  $config
	 * @param ContainerInterface                    $container
	 * @param \phpbb\controller\helper              $helper
	 * @param \phpbb\db\driver\driver_interface     $db
	 * @param \phpbb\language\language              $lang
	 * @param \phpbb\log\log                        $log
	 * @param \phpbb\request\request                $request
	 * @param \phpbb\template\template              $template
	 * @param \phpbb\user                           $user
	 * @param string                                $root_path
	 * @param string                                $php_ext
	 */
	public function __construct(\phpbb\cache\driver\driver_interface $cache, \phpbb\config\config $config, ContainerInterface $container, \phpbb\controller\helper $helper, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\log\log $log, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $root_path, $table_prefix, $php_ext, $server = array())
	{
		$this->cache = $cache;
		$this->config = $config;
		$this->container = $container;
		$this->helper = $helper;
		$this->db = $db;
		$this->lang = array();
		$this->log = $log;
		$this->request = $request;
		$this->s = $request->variable('mode', 'generate');
		$this->l = $request->variable('l', array('ENCODING' => 'UTF-8'));
		/** POST 64 & GET 128 **/
		//$this->l = $this->phpbb_read('l', ($type | 64 | 128), '', false);
		
		/* set language_from to translator_default_lang */
		$this->language_from = (isset($this->config['translator_default_lang'])) ? $this->config['translator_default_lang'] : 'en'; 
		$this->translator_choice_lang = (isset($this->config['translator_choice_lang'])) ? $this->config['translator_choice_lang'] : '';
		
		// Requests
		$this->action = $request->variable('action', '');
		$this->page_id = $request->variable('page_id', 0);
		$this->currency_id = $request->variable('currency_id', 0);		
		
		/* general vars */
		$this->mode = $request->variable('mode', 'generate');
		$this->start = $request->variable('start', 0); 
		
		$this->set_file = $request->variable('set_file', '');
		$this->into = $request->variable('into', '');
		$this->ajax = $request->variable('ajax', 0);
		
		/** **/
		$this->cookies	= array();		
		$this->template = $template;
		$this->user = $user;
		$this->language	= $language;		
		$this->root_path = $root_path;
		$this->forum_root_path = $root_path;
		$this->table_prefix = $table_prefix;
		$this->phpbb_admin_path = $root_path . 'adm/';	
		$this->php_ext = $php_ext;
		$this->mx_root_path = file_exists('./../../mx_meta.inc') ? './../../' : $root_path;
		/*
		* Read main mxp config file
		*/
		include_once($this->mx_root_path . 'config.' . $php_ext);
		$this->mx_table_prefix = !empty($mx_table_prefix) ? $mx_table_prefix : 'mx_';
		define('MXP_MODULE_TABLE', $mx_table_prefix . 'module');
		
		$this->module_root_path = $root_path . 'ext/orynider/translator/';
		//print_r($this->forum_root_path);
		if (!empty($config['version'])) 
		{
			if ($config['version']  >= '4.0.0')
			{			
				$this->backend = 'phpbb4';
			}		
			if (($config['version']  >= '3.3.0') && ($config['version'] < '4.0.0'))
			{			
				$this->backend = 'proteus';
			}
			if (($config['version']  >= '3.2.0') && ($config['version'] < '3.3.0'))
			{			
				$this->backend = 'rhea';
			}
			if (($config['version']  >= '3.1.0') && ($config['version'] < '3.2.0'))
			{			
				$this->backend = 'ascraeus';
			}
			if (($config['version']  >= '3.0.0') && ($config['version'] < '3.1.0'))
			{			
				$this->backend = 'olympus';
			}
			if (($config['version']  >= '2.0.0') && ($config['version'] < '3.0.0'))
			{			
				$this->backend = 'phpbb2';
			}
			if (($config['version']  >= '1.0.0') && ($config['version'] < '2.0.0'))
			{			
				$this->backend = 'phpbb';
			}			
		}
		else if (!empty($config['portal_backend']))
		{			
			$this->backend = $config['portal_backend'];
		}
		else
		{			
			$this->backend = 'internal';
		}
		
		$this->portal_block = isset($config['portal_backend']) ? true : false;
		
		if ($config['version'] < '3.1.0')
		{			
			define('EXT_TABLE',	$table_prefix . 'ext');
		}
		
		$this->trans = $this->container->get('orynider.translator.googletranslater');
		
		$language = $this->request->is_set_post('language') ? $this->request->variable('language', array('into' => 'en')) : array('into' => 'en');
		$translate = $this->request->is_set_post('translate') ? $this->request->variable('translate', array('dir' => '', 'module' => 'modules/translator/', 'file' => 'common.php')) : array('dir' => '', 'module' => 'modules/translator/', 'file' => 'common.php');
		$translate['dir'] = isset($translate['dir']) ? $translate['dir'] : $this->request->variable('dir', 'language/');
		$translate['file'] = isset($translate['file']) ? $translate['file'] : $this->request->variable('file', 'common.php');
		$this->language_into = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'language_into', $language['into']);
		$this->dir_select_from = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'dir_select_from', $translate['dir']);
		$this->dir_select_into = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'dir_select_into', $translate['dir']);
		$this->dir_select = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'dir_select', $translate['dir']);
		$this->module_select = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'module_select', $translate['module']);
		$this->module_file = $this->phpbb_cookie(MXP_LANG_TOOLS_COOKIE_NAME . 'module_file', $translate['file']);
		
		$this->phpbb_get_lang_list();
		$this->get_module_list();
		$this->get_dir_list();
		$this->get_file_list();
		
		/**
		 * SELECT encoding of language file
		 */
		if ($config['version'] < '3.0.0')
		{			
			$lang_enc = $this->_load_file_to_translate($root_path . 'language/' . $this->language_from . '/lang_main.' . $phpEx);
			$lang_enc = $this->_load_file_to_translate($root_path . 'language/' . $this->language_into . '/lang_main.' . $phpEx);
		}
		else		
		{
			$lang_enc['ENCODING'] = 'UTF-8';
		}
		//$lang_enc['ENCODING'] = 'Windows-1250';
		if (isset($lang_enc['ENCODING']) && $lang_enc != '')
		{
			$this->file_encoding = $lang_enc['ENCODING'];
		}
		
		$original_file_path1 = (($this->s == 'MODS') ? $this->module_select : ($this->s == 'phpbb_ext' ? $this->module_select : '')) . (!empty($this->gen_select_list('in_array', 'dirs')) ? $this->dir_select_from : 'language/' . $this->language_from) . '/' . $this->module_file;
		$translate_file_path1 = (($this->s == 'MODS') ? $this->module_select : ($this->s == 'phpbb_ext' ? $this->module_select : '')) . (!empty($this->gen_select_list('in_array', 'dirs')) ? $this->dir_select_into : 'language/' . $this->language_into) . '/' . $this->module_file;		
		$original_file_path = (($this->s == 'MODS') ? $this->module_select : ($this->s == 'phpbb_ext' ? $this->module_select : '')) . 'language/' . $this->language_from . '/' . $this->module_file;
		$translate_file_path = (($this->s == 'MODS') ? $this->module_select : ($this->s == 'phpbb_ext' ? $this->module_select : '')) . 'language/' . $this->language_into . '/' . $this->module_file;		
		
		$this->file_save_path = $this->root_path . $translate_file_path;
	}
	
	/**
	 * Display the options a user can configure for this extension
	 *
	 * @return void
	 * @access public
	 */
	public function display_translate($tpl_name, $page_title)
	{
		if (!defined('IN_AJAX'))
		{
			define('IN_AJAX', (isset($_GET['ajax']) && ($this->ajax == 1) && ($this->server['HTTP_SEREFER'] = $this->server['PHP_SELF'])) ? 1 : 0);
		}
		$phpEx = $this->php_ext;
		// Requests
		$action = $this->request->variable('action', '');
		$page_id = $this->request->variable('page_id', 0);
		$currency_id = $this->request->variable('currency_id', 0);		
		$this->parent_id = $this->request->variable('parent_id', 0);		
		/* general vars */
		$mode = $this->request->variable('mode', 'generate');
		$start = $this->request->variable('start', 0);  
		$s = $this->request->variable('mode', 'generate');	
		/* */	
		if (IN_AJAX == 0)
		{
			$lang['ENCODING'] = $this->file_encoding;
			if (isset($_POST['save']) || isset($_POST['download']))
			{
				$this->file_preparesave();
			}
			if (isset( $_POST['save']))
			{
				$this->file_save();
			}
			else if (isset( $_POST['download']))
			{
				$this->file_download();
			}			
			$this->user->add_lang_ext('orynider/translator', 'info_acp_translator');
			$this->user->add_lang('acp/board');			
			//$tpl_name = 'lang_translate';
			//$page_title = $this->lang('ACP_MX_LANGTOOLS_TITLE');			
			/** Only allow founders to view/manage these settings
			if ($this->user->data['user_type'] != USER_FOUNDER)
			{
				trigger_error($user->lang('ACP_FOUNDER_MANAGE_ONLY'), E_USER_WARNING);
			}
			else
			{
				$this->is_admin = USER_FOUNDER;
			}
			*/						
			// Create a form key for preventing CSRF attacks
			add_form_key($tpl_name);
			// Create an array to collect errors that will be output to the user
			$errors = array();		
			// Is the form being submitted to us?
			if ($submit = $this->request->is_set_post('submit'))
			{
				// Test if the submitted form is valid
				if (!check_form_key($tpl_name))
				{
					$errors[] = $this->lang->lang('FORM_INVALID');
					//trigger_error('FORM_INVALID');
				}
				
				$s_errors = (bool) count($errors);			
				// If no errors, process the form data
				if (empty($errors))
				{
					// Add option settings change action to the admin log
					$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'ACP_TRANSLATOR_SETTINGS_LOG');
					// Option settings have been updated and logged
					// Confirm this to the user and provide link back to previous page
					trigger_error($this->lang('ACP_TRANSLATOR_SETTINGS_CHANGED') . adm_back_link($this->u_action));
				}		
				trigger_error($this->lang('TRANSLATOR_CONFIG_SAVED') . adm_back_link($this->u_action));
			}		
			//$submit = $this->request->is_set('submit');			
			$this->cache->destroy('_translator');
			$this->cache->destroy('_translator_module');			
			$this->template->assign_block_vars('file_to_translate_select', array());
			
			$basename = basename( __FILE__);
			$mx_root_path = (defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? generate_board_url() . '/' : $this->root_path;
			$module_root_path = $this->root_path . 'ext/orynider/translator/';
			$admin_module_root_path = $this->root_path . 'adm/';		

			$s_action = $admin_module_root_path . $basename;
			$params = $this->request->server('QUERY_STRING');
			//$params = $this->server['QUERY_STRING'];			
			if ($this->request->is_set_post('submit'))
			{
				if (!check_form_key('orynider/translator'))
				{
					trigger_error('FORM_INVALID', E_USER_WARNING);
				}
			}			
			/** -------------------------------------------------------------------------
			* Extend User Style with module lang and images
			* Usage:  $user->extend(LANG, IMAGES, '_core', 'img_file_in_dir', 'img_file_ext')
			* Switches:
			* - LANG: MX_LANG_MAIN (default), MX_LANG_ADMIN, MX_LANG_ALL, MX_LANG_NONE
			* - IMAGES: MX_IMAGES (default), MX_IMAGES_NONE
			** ------------------------------------------------------------------------- */
			$this->extend(false, false, 'all', 'icon_info', false);				
			/**
			* Reset custom module default style, once used.
			*/
			if (@file_exists($this->user_current_style_path . 'images/menu_icons/icon_info.gif'))
			{
				$img_info = $this->user_current_style_path . 'images/menu_icons/icon_info.gif';
			}
			else
			{
				$img_info = $this->default_current_style_path . 'images/menu_icons/icon_info.gif';
			}
			if (@file_exists( $this->user_current_style_path . 'images/menu_icons/icon_google.gif'))
			{
				$img_google = $this->user_current_style_path . 'images/menu_icons/icon_google.gif';
			}
			else
			{
				$img_google = $this->default_current_style_path . 'images/menu_icons/icon_google.gif';
			}			
			$params = !empty($params) ? $params : "&i=-orynider-translator-acp-translator_module&mode=".$mode;
			$this->u_action = !empty($this->u_action) ? $this->u_action : '';
			/* * /	
			print_r($this->gen_select_list( 'html', 'dirs', $this->dir_select)); 
			/* */					
			$this->template->assign_vars(array( // #
				'TH_COLOR2' => isset($theme['th_color2']) ? isset($theme['th_color2']) : '#fff',
				
				'S_LANGUAGE_INTO' => $this->gen_select_list( 'html', 'language', $this->language_into, $this->language_from),
				'S_MODULE_LIST' => $this->gen_select_list( 'html', 'modules', $this->module_select),
				'S_DIR_LIST' => $this->gen_select_list( 'html', 'dirs', $this->dir_select),
				'S_FILE_LIST' => $this->gen_select_list( 'html', 'files', $this->module_file),				
				'S_ACTION' => $this->u_action . '?' . str_replace('&amp;', '&', $params),
				'S_ACTION_AJAX' => $this->u_action . '?' . str_replace('&amp;', '&', $params) . '&ajax=1',
				
				'L_RESET' => isset($this->user->lang['RESET']) ? $this->user->lang['RESET'] : 'Reset',
				'IMG_INFO' => $img_info,
				'IMG_GOOGLE' => $img_google,				
				'I_LANGUAGE' => $this->language_into,
				'I_MODULE' => $this->module_select,
				'I_DIR' => $this->dir_select,
				'I_FILE' => $this->module_file,			
			));		
			/* */
			$this->assign_template_vars($this->template);
			$this->template->assign_vars( array( // #
				'L_MX_MODULES' =>  isset($this->user->lang['MX_MODULES']) ? $this->user->lang['MX_MODULES'] : 'MX_Modules',
			));
			if (($this->s == 'MODS') || ($this->s == 'phpbb_ext'))
			{
				$this->template->assign_block_vars('file_to_translate_select.modules', array());
				$this->template->assign_block_vars('modules', array());
			}
			/*
			if(!empty($this->gen_select_list('in_array', 'dirs')))
			{
				$this->template->assign_block_vars('file_to_translate_select.dirs', array());
				$this->template->assign_block_vars('dirs', array());
			}
			*/
			$this->file_translate();			
			//page_footer();
		}
		else
		{ // AJAX
			$tpl_name = 'selects';
			//$this->template->set_filenames( array('body' => 'selects.html'));
			add_form_key($tpl_name);			
			$style = "width:100%;"; 
			if ($this->into == 'language')
			{
				$option_list = $this->gen_select_list('html', 'language', $this->language_into, $this->language_from);
				$name = 'language[into]';
				$id = 'f_lang_into';
			}
			/* */
			if ($this->into == 'modules')
			{		
				$option_list = $this->gen_select_list('html', 'modules', $this->module_select);
				$name = 'translate[module]';
				$id = 'f_select_file';
			}			
			/* */
			if ($this->into == 'dirs')
			{		
				$option_list = $this->gen_select_list('html', 'dirs', $this->dir_select);
				$name = 'translate[dir]';
				$id = 'f_select_file';
			}
			/* */			
			if ($this->into == 'files')
			{		
				$option_list = $this->gen_select_list('html', 'files', $this->module_file);
				$name = 'translate[file]';
				$id = 'f_select_file';
			}			
			$this->template->assign_block_vars('ajax_select', array(
				'NAME'		=> $name,
				'ID'		=> $id,
				'STYLE'		=> $style,
				'OPTIONS'	=> $option_list,
			));
			//$this->template->pparse('body');
		}
	}
	
	/**
	* Get lang key or value
	* To do: update this->user->lang[] into this->language->lang()
	* Not used, to be removed in 1.0.0-RC4
	* @return unknown
	 */	
	function get_lang($key)
	{
		return ((!empty($key) && isset($this->user->lang[$key])) ? $this->user->lang[$key] : $key);
	}
	
	/**
	*
	* List all countries for witch languages files are installed 
	* and multilangual files uploaded
	* $this->countries = $this->get_countries()
	*/
	function get_countries()
	{
		// get all countries installed
		$countries = array();
		$dir = @opendir($this->root_path . 'language');
		while ($file = @readdir($dir))
		{
			if (preg_match('#^lang_#i', $file) && !is_file($this->root_path . 'language/' . $file) && !is_link($this->root_path . 'language/' . $file))
			{
				$filename = trim(str_replace('lang_', '', $file));
				$displayname = preg_replace("/^(.*?)_(.*)$/", "\\1 [ \\2 ]", $filename);
				$displayname = preg_replace("/\[(.*?)_(.*)\]/", "[ \\1 - \\2 ]", $displayname);
				$countries[$file] = ucfirst($displayname);
			}
		}
		@closedir($dir);
		@asort($countries);

		return $countries;
	}

	function get_packs()
	{
		global $countries;

		/* MG Lang DB - BEGIN */
		$skip_files = array(('lang_bbcode.' . $this->php_ext), ('lang_faq.' . $this->php_ext), ('lang_rules.' . $this->php_ext));
		/* MG Lang DB - END */

		// get all the extensions installed
		$packs = array();
		
		@reset($countries);
		
		while (list($country_dir, $country_name) = @each($countries))
		{
			$dir = @opendir($this->root_path . 'language/' . $country_dir);
			
			while ($file = @readdir($dir))
			{
				if ( ( $file == '.' || $file == '..') || (substr(strrchr($file, '.'), 1) !== $this->php_ext) || (strpos($file, 'lang_') === false))
				{
					continue;
				}				
				
				$pattern = 'lang_u';
				if (preg_match('/' . $pattern . '/i', $file))
				//if(preg_match("/^lang_user_created.*?\." . $this->php_ext . "$/", $file))
				//if((preg_match("/^lang_user_created.*?\." . $this->php_ext . "$/", $file)) || (preg_match("/^lang_main.*?\." . $this->php_ext . "$/", $file)))
				//if((preg_match("/^lang_user_created.*?\." . $this->php_ext . "$/", $file)) || (preg_match("/^lang_admin.*?\." . $this->php_ext . "$/", $file)))
				//if(preg_match("/^lang_user_created.*?\." . $this->php_ext . "$/", $file))
				{
					/* MG Lang DB - BEGIN */
					if (!in_array($file, $skip_files))
					/* MG Lang DB - END */
					{
						$displayname = $file;
						$packs[$file] = $displayname;
					}
				}
				/* MG Lang DB - BEGIN */
				if(preg_match("/^lang_extend_.*?\." . $this->php_ext . "$/", $file))
				{
					$displayname = trim(str_replace(('.' . $this->php_ext), '', str_replace('lang_extend_', '', $file)));
					$packs[$file] = $displayname;
				}
				/* MG Lang DB - END */
			}
			@closedir($dir);
		}
		/* MG Lang DB - BEGIN */
		/*
		$packs['lang'] = '_phpBB';
		$packs['custom'] = '_custom';
		*/
		/* MG Lang DB - END */
		@asort($packs);

		return $packs;
	}

	function read_one_pack($country_dir, $pack_file, &$entries)
	{
		global $countries, $packs;

		// get filename
		$file = $this->root_path . 'language/' . $country_dir . '/' . $pack_file;
		if (($pack_file != 'lang') && ($pack_file != 'custom') && !file_exists($file))
		{
			//die('This file doesn\'t exist: ' . $file);
			echo('This file doesn\'t exist: ' . $file . '<br />');
		}

		// process first admin then standard keys
		for ($i = 0; $i < 2; $i++)
		{
			$lang_extend_admin = ($i == 0);

			/* MG Lang DB - BEGIN */
			// fix the filename for standard keys
			if ($pack_file == 'lang')
			{
				$file = $this->root_path . 'language/' . $country_dir . '/' . ($lang_extend_admin ? 'lang_admin.' : 'lang_main.') . $this->php_ext;
			}
			// fix the filename for custom keys
			if ($pack_file == 'custom')
			{
				$file = $this->root_path . 'language/' . $country_dir . '/' . 'lang_extend.' . $this->php_ext;
			}
			/* MG Lang DB - END */

			// process
			$lang = array();
			@include($file);
			@reset($lang);
			while (list($key_main, $data) = @each($lang))
			{
				$custom = ($pack_file == 'custom');
				$first = !is_array($data);
				while ((is_array($data) && (list($key_sub, $value) = @each($data))) || $first)
				{
					$first = false;
					if (!is_array($data))
					{
						$key_sub = '';
						$value = $data;
					}
					$pack = $pack_file;
					$original = '';
					if ($custom && isset($entries['pack'][$key_main][$key_sub]))
					{
						$pack = $entries['pack'][$key_main][$key_sub];
						$original = $entries['pack'][$key_main][$key_sub][$country_dir];
					}
					$entries['pack'][$key_main][$key_sub] = $pack;
					$entries['value'][$key_main][$key_sub][$country_dir] = $value;
					$entries['original'][$key_main][$key_sub][$country_dir] = $original;
					$entries['admin'][$key_main][$key_sub] = $lang_extend_admin;
					// status : 0 = original, 1 = modified, 2 = added
					$entries['status'][$key_main][$key_sub][$country_dir] = (!$custom ? 0 : (($pack != $pack_file) ? 1 : 2));
				}
			}
		}
	}
	
	/**
	* Get entries (all lang keys) from all multilangual files of a package
	* $old_entries = $this->get_entries(false);
	*/		
	function get_entries($modified = true)
	{
		global $config;
		global $countries, $packs;

		// init
		$entries = array();

		// process by countries first
		/* MG Lang DB - BEGIN */
		/*
		@reset($countries);
		while (list($country_dir, $country_name) = @each($countries))
		{
			// phpBB lang keys
			$pack_file = 'lang';
			$this->read_one_pack($country_dir, $pack_file, $entries);
		}

		// process other packs except custom one
		@reset($countries);
		while (list($country_dir, $country_name) = @each($countries))
		{
			@reset($packs);
			while (list($pack_file, $pack_name) = @each($packs))
			{
				if (($pack_file != 'lang') && ($pack_file != 'custom'))
				{
					$this->read_one_pack($country_dir, $pack_file, $entries);
				}
			}
		}
		*/
		/* MG Lang DB - END */

		/* MG Lang DB - BEGIN */
		@reset($countries);
		while (list($country_dir, $country_name) = @each($countries))
		{
			@reset($packs);
			while (list($pack_file, $pack_name) = @each($packs))
			{
				$this->read_one_pack($country_dir, $pack_file, $entries);
			}
		}
		/* MG Lang DB - END */

		// process the added/modified keys
		if ($modified)
		{
			/* MG Lang DB - BEGIN */
			@reset($countries);
			while (list($country_dir, $country_name) = @each($countries))
			{
				$pack_file = 'custom';
				$this->read_one_pack($country_dir, $pack_file, $entries);
			}
			/* MG Lang DB - END */

			// add the missing keys in a language
			$default_lang = 'lang_' . $config['default_lang'];
			$english_lang = 'lang_english';
			@reset($entries['pack']);
			while (list($key_main, $data) = @each($entries['pack']))
			{
				@reset($data);
				while (list($key_sub, $pack_file) = @each($data))
				{
					// add the key to the default lang if missing by using the english one
					if (!isset($entries['value'][$key_main][$key_sub][$default_lang]))
					{
						// add the key to english lang if missing
						if (!isset($entries['value'][$key_main][$key_sub][$english_lang]))
						{
							// find the first not empty value
							$found = false;
							$new_value = '';
							@reset($entries['value'][$key_main][$key_sub]);
							while (list($country_dir, $value) = @each($entries['value'][$key_main][$key_sub]))
							{
								$found = !empty($value);
								if ($found)
								{
									$new_value = $value;
								}
							}
							// add it (even if empty)
							$entries['value'][$key_main][$key_sub][$english_lang] = $new_value;
							$entries['status'][$key_main][$key_sub][$english_lang] = 2; // 2=added
						}

						// fill the default lang
						if ($default_lang!= $english_lang)
						{
							$entries['value'][$key_main][$key_sub][$default_lang] = $entries['value'][$key_main][$key_sub][$english_lang];
							$entries['status'][$key_main][$key_sub][$default_lang] = 2; // 2=added
						}
					}

					// process all langs for this key
					@reset($countries);
					while (list($country_dir, $country_name) = @each($countries))
					{
						if (!isset($entries['value'][$key_main][$key_sub][$country_dir]))
						{
							$entries['value'][$key_main][$key_sub][$country_dir] = $entries['value'][$key_main][$key_sub][$default_lang];
							$entries['status'][$key_main][$key_sub][$country_dir] = 2; // 2=added
						}
					}
				}
			}
		}

		// all is done : return the result
		return $entries;
	}
}
?>