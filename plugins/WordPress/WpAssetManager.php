<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Piwik\AssetManager;
use Piwik\Plugins\WordPress\AssetManager\NeverDeleteOnDiskUiAsset;
use Piwik\Translate;
use Piwik\Version;

if (!defined( 'ABSPATH')) {
    exit; // if accessed directly
}

class WpAssetManager extends AssetManager
{
	public function __construct() {
		parent::__construct();
	}

	public function getMergedCoreJavaScript()
	{
		$path = rtrim(plugin_dir_path( MATOMO_ANALYTICS_FILE ), '/') . '/assets/js';
		$file = 'asset_manager_core_js.js';
		return new NeverDeleteOnDiskUiAsset($path, $file);
	}

	public function getJsInclusionDirective()
	{
		$result = "<script type=\"text/javascript\">\n" . Translate::getJavascriptTranslations() . "\n</script>";

		$jsFiles = array();
		$jsFiles[] = "jquery/jquery.js";
		$jsFiles[] = 'jquery/ui/widget.min.js';
		$jsFiles[] = 'jquery/ui/mouse.min.js';
		$jsFiles[] = 'jquery/ui/selectable.min.js';
		$jsFiles[] = 'jquery/ui/autocomplete.min.js';
		$jsFiles[] = 'jquery/ui/core.min.js';
		$jsFiles[] = 'jquery/ui/position.min.js';
		$jsFiles[] = 'jquery/ui/resizable.min.js';
		$jsFiles[] = 'jquery/ui/datepicker.min.js';
		$jsFiles[] = 'jquery/ui/dialog.min.js';
		$jsFiles[] = 'jquery/ui/menu.min.js';
		$jsFiles[] = 'jquery/ui/draggable.min.js';
		$jsFiles[] = 'jquery/ui/droppable.min.js';
		$jsFiles[] = 'jquery/ui/tooltip.min.js';
		$jsFiles[] = 'jquery/ui/sortable.min.js';
		$jsFiles[] = 'jquery/ui/spinner.min.js';
		$jsFiles[] = 'jquery/ui/tabs.min.js';
		$jsFiles[] = 'jquery/ui/button.min.js';

		foreach ($jsFiles as $jsFile) {
			$result .= sprintf(self::JS_IMPORT_DIRECTIVE, '../../../../wp-includes/js/' . $jsFile);
		}

		$result .= "<script type=\"text/javascript\">window.$ = jQuery;</script>";
		$result .= sprintf(self::JS_IMPORT_DIRECTIVE, '../assets/js/asset_manager_core_js.js?v=' . Version::VERSION);

		if ($this->isMergedAssetsDisabled()) {
			$this->getMergedNonCoreJSAsset()->delete();
			$result .= $this->getIndividualJsIncludesFromAssetFetcher($this->getNonCoreJScriptFetcher());
		} else {
			$result .= sprintf(self::JS_IMPORT_DIRECTIVE, self::GET_NON_CORE_JS_MODULE_ACTION);
		}

		return $result;
	}
}
