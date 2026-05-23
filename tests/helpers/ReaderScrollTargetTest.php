<?php

use PHPUnit\Framework\TestCase;

class ReaderScrollTargetTest extends TestCase
{
	public function testPageChangesScrollToReaderBarInAvailableThemes()
	{
		foreach (array('dazen-skin', 'default') as $theme)
		{
			$source = file_get_contents(dirname(__DIR__, 2) . '/content/themes/' . $theme . '/views/read.php');

			$this->assertStringContainsString("var \$readerBar = jQuery('.panel .topbar').first();", $source, $theme);
			$this->assertStringContainsString("var scrollTarget = \$readerBar.length ? \$readerBar.offset().top : jQuery('#page').offset().top;", $source, $theme);
			$this->assertStringContainsString('var readerTop = Math.max(scrollTarget - 6, 0);', $source, $theme);
			$this->assertStringNotContainsString("var readerTop = Math.max(jQuery('#page').offset().top - 6, 0);", $source, $theme);
		}
	}
}
