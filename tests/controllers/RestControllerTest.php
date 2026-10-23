<?php

use PHPUnit\Framework\TestCase;

if (!function_exists('config_item'))
{
	function config_item($key)
	{
		return isset($GLOBALS['__test_rest_config'][$key])
			? $GLOBALS['__test_rest_config'][$key]
			: FALSE;
	}
}

require_once dirname(__DIR__, 2) . '/application/libraries/REST_Controller.php';

class RestControllerTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['__test_rest_config'] = array(
			'rest_default_format' => 'json',
			'rest_enable_keys' => FALSE,
			'rest_enable_logging' => FALSE,
		);
	}

	public function testConstructorInitializesRestRequestResponseAndStateObjects()
	{
		$controller = new RestControllerConstructorProbe();

		$this->assertSame('get', $controller->requestState()->method);
		$this->assertNull($controller->requestState()->format);
		$this->assertNull($controller->requestState()->body);
		$this->assertSame('json', $controller->responseState()->format);
		$this->assertNull($controller->responseState()->lang);
		$this->assertInstanceOf(stdClass::class, $controller->restState());
	}
}

class RestControllerConstructorProbe extends REST_Controller
{
	public $load;
	public $input;
	public $uri;
	public $config;
	public $overrides_array;
	public $auth_override;

	public function __construct()
	{
		$this->load = new RestControllerStubLoader();
		$this->input = new RestControllerStubInput();
		$this->uri = new RestControllerStubUri();
		$this->config = new RestControllerStubConfig();

		parent::__construct();
	}

	public function requestState()
	{
		return $this->request;
	}

	public function responseState()
	{
		return $this->response;
	}

	public function restState()
	{
		return $this->rest;
	}
}

class RestControllerStubLoader
{
	public function config($name)
	{
		return TRUE;
	}

	public function library($name)
	{
		return TRUE;
	}
}

class RestControllerStubInput
{
	public function server($key)
	{
		return $key === 'REQUEST_METHOD' ? 'GET' : NULL;
	}

	public function post($key = NULL)
	{
		return NULL;
	}

	public function is_cli_request()
	{
		return TRUE;
	}

	public function is_ajax_request()
	{
		return FALSE;
	}
}

class RestControllerStubUri
{
	public function ruri_to_assoc()
	{
		return array();
	}

	public function uri_string()
	{
		return '';
	}
}

class RestControllerStubConfig
{
	public function item($key)
	{
		return config_item($key);
	}
}
