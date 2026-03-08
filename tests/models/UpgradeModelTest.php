<?php

use PHPUnit\Framework\TestCase;

if (!class_exists('CI_Model'))
{
	class CI_Model
	{
		public function __construct()
		{
		}
	}
}

if (!defined('FOOLSLIDE_VERSION'))
{
	define('FOOLSLIDE_VERSION', '2.0.1');
}

require_once dirname(__DIR__, 2) . '/application/models/upgrade_model.php';

class UpgradeModelTest extends TestCase
{
	public function testCheckLatestReturnsNewerGithubRelease()
	{
		$model = new UpgradeModelStub(array(
			$this->release('v.2.1.0', 'Notes'),
			$this->release('v.2.0.1', 'Current')
		));

		$result = $model->check_latest();

		$this->assertCount(1, $result);
		$this->assertSame('2', $result[0]->version);
		$this->assertSame('1', $result[0]->subversion);
		$this->assertSame('0', $result[0]->subsubversion);
		$this->assertSame('Notes', $result[0]->changelog);
		$this->assertSame('https://example.test/v.2.1.0.zip', $result[0]->download);
	}

	public function testCheckLatestSkipsDraftsAndPrereleases()
	{
		$model = new UpgradeModelStub(array(
			$this->release('v.2.2.0', 'draft', TRUE, FALSE),
			$this->release('v.2.1.5', 'pre', FALSE, TRUE),
			$this->release('v.2.1.0', 'stable')
		));

		$result = $model->check_latest();

		$this->assertCount(1, $result);
		$this->assertSame('1', $result[0]->subversion);
		$this->assertSame('0', $result[0]->subsubversion);
	}

	public function testCheckLatestForceReturnsLatestStableEvenWhenUpToDate()
	{
		$model = new UpgradeModelStub(array(
			$this->release('v.2.0.1', 'Current release')
		));

		$result = $model->check_latest(TRUE);

		$this->assertCount(1, $result);
		$this->assertSame('2', $result[0]->version);
		$this->assertSame('0', $result[0]->subversion);
		$this->assertSame('1', $result[0]->subsubversion);
	}

	public function testFlattenDownloadedReleaseMovesGithubArchiveContentsToExpectedPath()
	{
		$model = new UpgradeModelStub(array());
		$base = sys_get_temp_dir() . '/foolslide_upgrade_' . uniqid('', TRUE);
		mkdir($base . '/content/cache/upgrade/fcibecchini-FoOlSlide-Improved-123/application/models', 0777, TRUE);
		file_put_contents($base . '/content/cache/upgrade/fcibecchini-FoOlSlide-Improved-123/application/models/upgrade2_model.php', '<?php');
		file_put_contents($base . '/content/cache/upgrade/upgrade.zip', 'zip');

		$oldCwd = getcwd();
		chdir($base);

		try
		{
			$this->assertTrue($model->flatten_downloaded_release());
			$this->assertFileExists($base . '/content/cache/upgrade/application/models/upgrade2_model.php');
			$this->assertDirectoryDoesNotExist($base . '/content/cache/upgrade/fcibecchini-FoOlSlide-Improved-123');
		}
		finally
		{
			chdir($oldCwd);
			$this->deleteDirectory($base);
		}
	}

	private function release($tagName, $body, $draft = FALSE, $prerelease = FALSE)
	{
		return (object) array(
			'tag_name' => $tagName,
			'body' => $body,
			'draft' => $draft,
			'prerelease' => $prerelease,
			'zipball_url' => 'https://example.test/' . $tagName . '.zip',
			'html_url' => 'https://example.test/' . $tagName,
			'name' => $tagName
		);
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

class UpgradeModelStub extends Upgrade_model
{
	private $releases;

	public function __construct(array $releases)
	{
		$this->releases = $releases;
		$this->github_repo = 'fcibecchini/FoOlSlide-Improved';
		$this->release_api = 'https://api.github.com/repos/' . $this->github_repo . '/releases';
	}

	function fetch_github_releases()
	{
		return $this->releases;
	}
}
