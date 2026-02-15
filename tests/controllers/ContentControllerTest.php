<?php

use PHPUnit\Framework\TestCase;

class ContentControllerTest extends TestCase
{
	private function newController()
	{
		return new ContentTestController();
	}

	public function testLastIndexOfFindsLastOccurrence()
	{
		$controller = $this->newController();

		$this->assertSame(11, $controller->_lastIndexOf('chapter_abc_def', '_'));
	}

	public function testLastIndexOfReturnsMinusOneWhenNotFound()
	{
		$controller = $this->newController();

		$this->assertSame(-1, $controller->_lastIndexOf('chapterabcdef', '_'));
	}
}

class ContentTestController extends Content
{
	public function __construct()
	{
	}
}
