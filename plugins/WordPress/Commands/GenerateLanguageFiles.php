<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Commands;

use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\LanguagesManager;
use Piwik\Translation\Translator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 */
class GenerateLanguageFiles extends ConsoleCommand
{
	protected function configure()
	{
		$this->setName('wordpress:generate-language-files');
		$this->setDescription('Generate language files');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pluginRoot = plugin_dir_path(MATOMO_ANALYTICS_FILE);
		require_once $pluginRoot . 'vendor/autoload.php';

		foreach (LanguagesManager\API::getInstance()->getAvailableLanguages() as $language) {
			/** @var Translator $translator */
			$translator = StaticContainer::get(Translator::class);
			$translator->setCurrentLanguage($language);

			$translationsGettext = new \Gettext\Translations();
			$translationsGettext->setLanguage($language);

			// todo need to get translations for the plugin file in there somehow
			if ($language === 'en') {
				foreach (Filesystem::globr($pluginRoot .'classes', '*.php') as $file) {
					\Gettext\Extractors\PhpCode::fromFile($file, $translationsGettext);
				}
			}

			// TODO we always need to make sure to set a default value for all translations probably... if a translation
			// for a different language is not there, we probably need to set the english translation manually.

			foreach ($translator->getAllTranslations() as $group => $translations) {
				foreach ($translations as $key => $translation) {
					$insertedTranslation = $translationsGettext->insert('', $group . '_' . $key);
					$insertedTranslation->setTranslation($translation);
				}
			}

			$translationsGettext->setDomain('matomo');
			$translationsGettext->toMoFile($pluginRoot . 'languages/matomo-'.$language.'.mo');
			$translationsGettext->toPoFile($pluginRoot . 'languages/matomo-'.$language.'.po');
		}
	}

}
