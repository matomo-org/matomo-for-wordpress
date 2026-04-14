<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace Piwik\Plugins\WordPress\Tracker;

use Piwik\Tracker\Request;

class RequestProcessor extends \Piwik\Tracker\RequestProcessor {
    public function manipulateRequest(Request $request) {
        do_action( 'matomo_tracker_manipulate_request', $request );
    }
}

