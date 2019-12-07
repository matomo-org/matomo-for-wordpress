<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Commands;

use Piwik\AssetManager;
use Piwik\Filesystem;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\Plugins\LanguagesManager\API;
use Piwik\Plugins\WordPress\WpAssetManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

if (!defined( 'ABSPATH')) {
	exit; // if accessed directly
}

class GenerateLangFiles extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('wordpress:generate-lang-files');
        $this->setDescription('Generate the core JS asset file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	    $languages = API::getInstance()->getAvailableLanguages();
	    $plugins = Plugin\Manager::getInstance()->loadAllPluginsAndGetTheirInfo();
	    foreach ($languages as $language) {
	    	$corePath = PIWIK_INCLUDE_PATH . '/lang/' . $language . '.json';
	    	$base = json_decode(file_get_contents($corePath), true);
	    	foreach ($plugins as $plugin => $pluginInfo) {
			    $file = PIWIK_INCLUDE_PATH . '/plugins/' . $plugin . '/lang/' . $language . '.json';
			    if (file_exists($file)) {
				    $mixin = json_decode(file_get_contents($file), true);
				    $base = array_merge($base, $mixin);

				    Filesystem::deleteFileIfExists($file);
			    }
		    }

		    file_put_contents($corePath, json_encode($base, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	    }

    }

}
