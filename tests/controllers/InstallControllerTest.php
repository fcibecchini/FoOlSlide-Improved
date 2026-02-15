<?php

use PHPUnit\Framework\TestCase;

class InstallControllerTest extends TestCase
{
	private function newController()
	{
		return new InstallTestController();
	}

	public function testExecEnabledReflectsDisableFunctionsIni()
	{
		$controller = $this->newController();
		$disabled = explode(',', (string) ini_get('disable_functions'));
		$expected = !in_array('exec', $disabled, true);

		$this->assertSame($expected, $controller->_exec_enabled());
	}

	public function testCheckReturnsFalseWhenConfigSampleIsMissing()
	{
		$controller = $this->newController();

		$this->withTempInstallTree(false, function () use ($controller) {
			$this->assertFalse($controller->_check());
			$this->assertNotEmpty($GLOBALS['__test_notices']);
			$this->assertSame('error', $GLOBALS['__test_notices'][0]['type']);
		});
	}

	public function testCheckReturnsTrueWhenRequirementsAreSatisfied()
	{
		$controller = $this->newController();

		$this->withTempInstallTree(true, function () use ($controller) {
			$this->assertTrue($controller->_check());
			$this->assertEmpty($GLOBALS['__test_notices']);
		});
	}

	private function withTempInstallTree($withConfigSample, callable $callback)
	{
		$base = sys_get_temp_dir() . '/foolslide_install_' . uniqid('', true);
		mkdir($base . '/content/themes', 0777, true);

		if ($withConfigSample)
		{
			mkdir($base . '/assets', 0777, true);
			file_put_contents($base . '/assets/config.sample.php', '<?php');
		}

		$oldCwd = getcwd();
		$GLOBALS['__test_notices'] = array();
		chdir($base);

		try
		{
			$callback();
		}
		finally
		{
			chdir($oldCwd);
			$this->deleteDirectory($base);
			$GLOBALS['__test_notices'] = array();
		}
	}

	private function deleteDirectory($path)
	{
		if (!is_dir($path))
		{
			return;
		}

		$items = scandir($path);
		foreach ($items as $item)
		{
			if ($item === '.' || $item === '..')
			{
				continue;
			}

			$fullPath = $path . '/' . $item;
			if (is_dir($fullPath))
			{
				$this->deleteDirectory($fullPath);
			}
			else
			{
				unlink($fullPath);
			}
		}

		rmdir($path);
	}
}

class InstallTestController extends Install
{
	public function __construct()
	{
	}
}
