<?php

define('BASEPATH', __DIR__ . '/../system/');
define('FCPATH', dirname(__DIR__) . '/');

if (!class_exists('PHPUnit\\Framework\\TestCase') && class_exists('PHPUnit_Framework_TestCase'))
{
	class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase');
}

if (!function_exists('_'))
{
	function _($string)
	{
		return $string;
	}
}

if (!function_exists('get_setting'))
{
	function get_setting($key)
	{
		return null;
	}
}

if (!function_exists('site_url'))
{
	function site_url($uri = '')
	{
		$uri = ltrim((string) $uri, '/');
		return 'http://localhost/' . $uri;
	}
}

if (!function_exists('show_404'))
{
	function show_404()
	{
		throw new RuntimeException('404');
	}
}

if (!function_exists('redirect'))
{
	function redirect($uri = '')
	{
		throw new RuntimeException('redirect:' . $uri);
	}
}

if (!function_exists('set_notice'))
{
	function set_notice($type, $message)
	{
		$GLOBALS['__test_notices'][] = array(
			'type' => $type,
			'message' => $message
		);
	}
}

class MY_Controller
{
	public function __construct()
	{
	}
}

class Public_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
}

class Install_Controller extends MY_Controller
{
	public $viewdata = array();

	public function __construct()
	{
		parent::__construct();
	}
}

require_once dirname(__DIR__) . '/application/controllers/content.php';
require_once dirname(__DIR__) . '/application/controllers/reader.php';
require_once dirname(__DIR__) . '/application/controllers/install.php';
