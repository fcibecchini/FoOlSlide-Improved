<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/application/helpers/notices_helper.php';

if (!function_exists('get_instance'))
{
	function &get_instance()
	{
		return $GLOBALS['__notices_test_ci'];
	}
}

class NoticesHelperTestCi
{
	public $flash_notice_data = array();
	public $notices = array();
}

class NoticesHelperTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['__notices_test_ci'] = new NoticesHelperTestCi();
		// AdminFormViewTest defines get_instance() first when the suite is loaded.
		$GLOBALS['__admin_form_test_ci'] = $GLOBALS['__notices_test_ci'];
	}

	public function testInlineNoticesStayVisibleWhileFloatingNoticesAutoDismiss()
	{
		$ci = get_instance();
		$ci->notices = array(array('type' => 'success', 'message' => 'Your message has been sent.'));

		$inline = get_notice_toasts('dazen-skin', 'inline');
		$this->assertStringContainsString('opacity: 1;', $inline);
		$this->assertStringNotContainsString('setTimeout', $inline);

		$ci->notices = array(array('type' => 'success', 'message' => 'Saved.'));
		$floating = get_notice_toasts('dazen-skin', 'floating');
		$this->assertStringContainsString('setTimeout', $floating);
	}
}
