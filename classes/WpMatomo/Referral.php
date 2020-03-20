<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WP_Roles;
use WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Every 90 days we show a please review notice until the user dismisses this notice or clicks on rate us.
 * We only show this notice on Matomo screens.
 * @package WpMatomo
 */
class Referral {

	const OPTION_NAME_REFERRAL_DISMISSED = 'matomo-referral-dismissed';

	/**
	 * @var int
	 */
	private $time;

	public function __construct() {
		$this->time = time();
	}

	/**
	 * @internal  for tests only
	 * @param int $time
	 */
	public function set_time($time) {
		$this->time = $time;
	}

	public function register_hooks() {
		$self = $this;

		add_action( 'wp_ajax_matomo_referral_dismiss_admin_notice', function () use ($self) {
			if ($self->should_show() && $self->can_refer()) {
				// no need for an nonce check here as it's nothing critical
				if (!empty($_POST['forever'])) {
					$self->never_show_again();
				} else {
					$self->dismiss();
				}
			}
		});
		add_action('admin_notices', function () use ($self) {
			if ($self->can_refer() && $self->should_show_on_screen()) {
				include 'views/referral.php';
			}
		});
	}

	public function should_show_on_screen() {
		$screen = get_current_screen();
		return $screen && $screen->id && strpos($screen->id, 'matomo-') === 0;
	}

	public function can_refer() {
		return current_user_can(Capabilities::KEY_VIEW);
	}

	public function never_show_again() {
		$tenYears = 60 * 60 * 24 * 365 * 10;
		update_option(self::OPTION_NAME_REFERRAL_DISMISSED, $this->time + $tenYears);
	}

	public function dismiss() {
		update_option(self::OPTION_NAME_REFERRAL_DISMISSED, $this->time, true);
	}

	public function should_show() {
		$dismissed = get_option(self::OPTION_NAME_REFERRAL_DISMISSED);

		if (!$dismissed) {
			// the first time we check... basically after install... should not be executed for another 90 days
			$this->dismiss();
			return false;
		}

		$ninetyDaysInSeconds = 60 * 60 * 24 * 90;

		if ($this->time > ($dismissed + $ninetyDaysInSeconds)) {
			return true;
		}

		return false;
	}


}
