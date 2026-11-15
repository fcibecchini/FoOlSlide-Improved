<?php

use PHPUnit\Framework\TestCase;

class UploaderViewsTest extends TestCase
{
	public function testUploaderLinkAndListHeadingExistInAllReaderThemes()
	{
		foreach (array('default', 'dazen-skin') as $theme)
		{
			$theme_root = FCPATH . 'content/themes/' . $theme . '/views/';
			$comic_view = file_get_contents($theme_root . 'comic.php');
			$latest_view = file_get_contents($theme_root . 'latest.php');

			$this->assertNotFalse($comic_view, $theme . ' series view should be readable.');
			$this->assertNotFalse($latest_view, $theme . ' chapter list should be readable.');
			$this->assertStringContainsString("site_url('uploader/'", $comic_view, $theme . ' series view should link to uploader chapters.');
			$this->assertStringContainsString('comic->creator', $comic_view, $theme . ' uploader link should use the series uploader ID.');
			$this->assertStringContainsString('Chapters uploaded by %s', $latest_view, $theme . ' chapter list should identify the uploader.');
		}
	}
}
