<?php

use PHPUnit\Framework\TestCase;

class CanonicalUrlTest extends TestCase
{
	protected function setUp(): void
	{
		$helper = dirname(__DIR__, 2) . '/application/helpers/canonical_url_helper.php';
		$this->assertFileExists($helper);
		require_once $helper;
	}

	public function testAlternateHostRedirectPreservesPathAndQuery()
	{
		$this->assertSame(
			'https://example.com/read/demo/page/2?mode=fit',
			canonical_request_url(
				'https://example.com',
				array(
					'HTTP_HOST' => 'www.example.com',
					'REQUEST_URI' => '/read/demo/page/2?mode=fit',
				)
			)
		);
	}

	public function testCanonicalHostDoesNotRedirect()
	{
		$this->assertFalse(
			canonical_request_url(
				'https://example.com',
				array(
					'HTTP_HOST' => 'example.com',
					'REQUEST_URI' => '/read/demo/page/2',
				)
			)
		);
	}

	public function testEmptyBaseUrlDoesNotRedirect()
	{
		$this->assertFalse(
			canonical_request_url(
				'',
				array(
					'HTTP_HOST' => 'www.example.com',
					'REQUEST_URI' => '/read/demo/page/2',
				)
			)
		);
	}

	public function testMalformedRequestUriFallsBackToRoot()
	{
		$this->assertSame(
			'https://example.com/',
			canonical_request_url(
				'https://example.com',
				array(
					'HTTP_HOST' => 'www.example.com',
					'REQUEST_URI' => "read/demo\r\nX-Test: injected",
				)
			)
		);
	}
}
