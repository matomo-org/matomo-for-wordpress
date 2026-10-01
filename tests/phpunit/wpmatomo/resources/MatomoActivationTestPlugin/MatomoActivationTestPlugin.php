<?php
/**
 * Matomo plugin used by PluginActivationInstallerTest, copied into the plugins directory.
 *
 * @package matomo
 */

namespace Piwik\Plugins\MatomoActivationTestPlugin;

use Piwik\Common;
use Piwik\Db;

class MatomoActivationTestPlugin extends \Piwik\Plugin {
	public function install() {
		Db::exec( 'CREATE TABLE IF NOT EXISTS `' . Common::prefixTable( 'matomo_activation_test' ) . '` (`id` INT NOT NULL)' );
	}

	public function uninstall() {
		Db::exec( 'DROP TABLE IF EXISTS `' . Common::prefixTable( 'matomo_activation_test' ) . '`' );
	}
}
