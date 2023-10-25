<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CoreConsole\Commands;

use Piwik\Config;
use Piwik\Filesystem;
use Piwik\SettingsPiwik;
use Piwik\Plugin\ConsoleCommand;

/**
 */
class DevelopmentEnable extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('development:enable');
        $this->setAliases(array('development:disable'));
        $this->setDescription('Enable or disable development mode. See config/global.ini.php in section [Development] for more information');
    }

    protected function doExecute(): int
    {
        $commandName = $this->getInput()->getFirstArgument();
        $enable      = (false !== strpos($commandName, 'enable'));

        $config      = Config::getInstance();
        $development = $config->Development;

        if ($enable) {
            $development['enabled'] = 1;
            $development['disable_merged_assets'] = 1;
            $message = 'Development mode enabled';
        } else {
            $development['enabled'] = 0;
            $development['disable_merged_assets'] = 0;
            $message = 'Development mode disabled';
        }

        $config->Development = $development;
        $config->forceSave();

        Filesystem::deleteAllCacheOnUpdate();

        $this->writeSuccessMessage(array($message));

        if ($enable && !SettingsPiwik::isGitDeployment()) {
            $comment = 'Development mode should be only enabled when installed through git. Not every development feature will be available.';
            $this->writeComment([$comment]);
        }

        return self::SUCCESS;
    }

}
