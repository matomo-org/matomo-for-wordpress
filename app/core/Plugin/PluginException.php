<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugin;

use Piwik\Common;
class PluginException extends \Exception
{
    public function __construct($pluginName, $message)
    {
        $pluginName = Common::sanitizeInputValue($pluginName);
        $message = Common::sanitizeInputValue($message);
        parent::__construct("There was a problem installing the plugin {$pluginName}: <br /><br />\n                {$message}\n                <br /><br />\n                If you want to hide this message you must remove the following line under the [Plugins] entry in your\n                'config/config.ini.php' file to disable this plugin.<br />\n                Plugins[] = {$pluginName}\n                <br /><br />If this plugin has already been installed, you must add the following line under the\n                [PluginsInstalled] entry in your 'config/config.ini.php' file:<br />\n                PluginsInstalled[] = {$pluginName}");
    }
    public function isHtmlMessage()
    {
        return true;
    }
}
