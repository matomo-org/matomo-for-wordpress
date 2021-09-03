<?php

use WpMatomo\Admin\PrivacySettings;

/**
 * @package matomo
 */
class OptOutTest extends MatomoAnalytics_TestCase {

	public function test_matomo_opt_out_no_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_MINIMAL );
		$this->assertSame(
			'<p id="matomo_opted_out_intro" style="display:none;">Opt-out complete; your visits to this website will not be recorded by the Web Analytics tool. Note that if you clear your cookies, delete the opt-out cookie, or if you change computers or Web browsers, you will need to perform the opt-out procedure again.</p><p id="matomo_opted_in_intro" >You may choose to prevent this website from aggregating and analyzing the actions you take here. Doing so will protect your privacy, but will also prevent the owner from learning from your actions and creating a better experience for you and other users.</p><form>
        <input type="checkbox" id="matomo_optout_checkbox" checked="checked"/>
        <label for="matomo_optout_checkbox"><strong>
        <span id="matomo_opted_in_label" >You are not opted out. Uncheck this box to opt-out.</span>
		<span id="matomo_opted_out_label" style="display:none;">You are currently opted out. Check this box to opt-in.</span>
        </strong></label></form><noscript><p><strong style="color: #ff0000;">This opt out feature requires JavaScript.</strong></p></noscript><p id="matomo_outout_err_cookies" style="display: none;"><strong>The tracking opt-out feature requires cookies to be enabled.</strong></p>',
			$result
		);
	}

	public function test_matomo_opt_out_all_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_FULL );
		$this->assertSame(
			'<p id="matomo_opted_out_intro" style="display:none;">Deaktivierung durchgeführt! Ihre Besuche auf dieser Webseite werden von der Webanalyse nicht mehr erfasst. Bitte beachten Sie, dass auch der Matomo-Deaktivierungs-Cookie dieser Webseite gelöscht wird, wenn Sie die in Ihrem Browser abgelegten Cookies entfernen. Außerdem müssen Sie, wenn Sie einen anderen Computer oder einen anderen Webbrowser verwenden, die Deaktivierungsprozedur nochmals absolvieren.</p><p id="matomo_opted_in_intro" >Sie haben die Möglichkeit zu verhindern, dass von Ihnen hier getätigte Aktionen analysiert und verknüpft werden. Dies wird Ihre Privatsphäre schützen, aber wird auch den Besitzer daran hindern, aus Ihren Aktionen zu lernen und die Bedienbarkeit für Sie und andere Benutzer zu verbessern.</p><form>
        <input type="checkbox" id="matomo_optout_checkbox" checked="checked"/>
        <label for="matomo_optout_checkbox"><strong>
        <span id="matomo_opted_in_label" >Ihr Besuch dieser Webseite wird aktuell von der Matomo Webanalyse erfasst. Diese Checkbox abwählen für Opt-Out.</span>
		<span id="matomo_opted_out_label" style="display:none;">Ihr Besuch dieser Webseite wird aktuell von der Matomo Webanalyse nicht erfasst. Diese Checkbox aktivieren für Opt-In.</span>
        </strong></label></form><noscript><p><strong style="color: #ff0000;">This opt out feature requires JavaScript.</strong></p></noscript><p id="matomo_outout_err_cookies" style="display: none;"><strong>Die Tracking opt-out Funktion benötigt aktivierte Cookies.</strong></p>',
			$result
		);
	}

	public function test_optOutJs_exists() {
		// see https://github.com/matomo-org/wp-matomo/issues/46
		$this->assertFileExists( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/plugins/CoreAdminHome/javascripts/optOut.js' );
	}

}
