<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/system/helpers/form_helper.php';

if (!function_exists('get_instance'))
{
	function &get_instance()
	{
		return $GLOBALS['__admin_form_test_ci'];
	}
}

if (!function_exists('buttoner'))
{
	function buttoner()
	{
		return '';
	}
}

if (!function_exists('config_item'))
{
	function config_item($key)
	{
		if ($key === 'charset')
		{
			return 'UTF-8';
		}

		return null;
	}
}

class AdminFormViewTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['__admin_form_test_ci'] = new AdminFormTestCi();
	}

	public function testGenericAdminFormWithFileInputUsesMultipartEncoding()
	{
		$table = '<input type="file" name="thumbnail" />';

		ob_start();
		include dirname(__DIR__, 2) . '/application/views/admin/form.php';
		$output = ob_get_clean();

		$this->assertStringContainsString('enctype="multipart/form-data"', $output);
	}
}

class AdminFormTestCi
{
	public $config;
	public $uri;
	public $security;

	public function __construct()
	{
		$this->config = new AdminFormTestConfig();
		$this->uri = new AdminFormTestUri();
		$this->security = new AdminFormTestSecurity();
	}
}

class AdminFormTestConfig
{
	public function site_url($uri = '')
	{
		return 'http://localhost/' . ltrim((string) $uri, '/');
	}

	public function item($key)
	{
		return false;
	}
}

class AdminFormTestUri
{
	public function uri_string()
	{
		return 'admin/series/add_new';
	}
}

class AdminFormTestSecurity
{
	public function get_csrf_token_name()
	{
		return 'csrf';
	}

	public function get_csrf_hash()
	{
		return 'hash';
	}
}
