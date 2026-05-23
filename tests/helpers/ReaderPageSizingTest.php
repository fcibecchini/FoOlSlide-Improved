<?php

use PHPUnit\Framework\TestCase;

class ReaderPageSizingTest extends TestCase
{
	public function testReaderUsesUnifiedBoundedSizingForWidePagesInAvailableThemes()
	{
		foreach (array('dazen-skin', 'default') as $theme)
		{
			$source = file_get_contents(dirname(__DIR__, 2) . '/content/themes/' . $theme . '/views/read.php');

			$this->assertStringContainsString('function fitReaderPageDimensions', $source, $theme);
			$this->assertStringContainsString('function readerBounds', $source, $theme);
			$this->assertStringContainsString('var fit_height = (page_width / page_height) > 1.2;', $source, $theme);
			$this->assertStringContainsString('fitReaderPageDimensions(page_width, page_height, bounds.max_width, bounds.max_height, fit_height)', $source, $theme);
			$this->assertStringContainsString('if (viewport_width > 768) max_width = 980;', $source, $theme);
			$this->assertStringNotContainsString('if (viewport_width <= 768)', $source, $theme);
			$this->assertStringNotContainsString("'max-width':'99999px'", $source, $theme);
			$this->assertStringNotContainsString("'overflow':'auto'", $source, $theme);
		}
	}
}
