<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/application/helpers/options_helper.php';

class OptionsHelperTest extends TestCase
{
	protected function setUp(): void
	{
		$GLOBALS['__test_settings'] = array();
	}

	public function testHasAboutPageReturnsFalseWhenAllSettingsAreEmpty()
	{
		$this->assertFalse(has_about_page());
	}

	public function testHasAboutPageReturnsTrueWhenAnyAboutSettingIsConfigured()
	{
		$GLOBALS['__test_settings']['fs_about_message'] = 'Configured';

		$this->assertTrue(has_about_page());
	}

	public function testAboutLabelFallsBackToItalianWhenGettextStringIsMissing()
	{
		$GLOBALS['__test_settings']['fs_gen_lang'] = 'it_IT.utf8';

		$this->assertSame('Chi Siamo', about_label('About'));
		$this->assertSame('Informazioni su Questo Sito', about_label('About This Site'));
	}

	public function testRandomStringUsesFullCharacterSetWithDeterministicSeed()
	{
		mt_srand(1234);
		$random = random_string(20);

		$this->assertSame(20, strlen($random));
		$this->assertSame('f3ut8gox7l31ymr6ooiu', $random);
		$this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $random);
	}
}
