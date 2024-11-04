<?php
/**
 * Test mail.
 *
 * @package matomo
 */

use Piwik\Mail;

class MailTest extends MatomoAnalytics_TestCase {
	public function test_mail() {
		if ( ! is_file( $this->plugin_file() ) ) {
			$this->fail( 'cannot run mail test without wp-mail-smtp, plugin must be installed and configured via consts locally' );
		}

		$this->manually_load_plugin();

		if ( ! $this->is_wp_mail_smtp_setup() ) {
			$this->fail( 'cannot run mail test without wp-mail-smtp and mailpit (WPMS_ON is undefined or false)' );
		}

		$mail = new Mail();
		$mail->setSubject( 'test subject' );
		$mail->setReplyTo( 'replyto@test.com', 'Reply To' );
		$mail->setWrappedHtmlBody( '<p>WP test-mail.php test (html)</p>' );
		$mail->setBodyText( 'WP test-mail.php test (text)' );
		$mail->addTo( 'sendto@test.com' );
		$mail->send();

		$actual_mail      = $this->get_caught_mail();
		$actual_mail_html = $actual_mail['HTML'];
		$actual_mail_text = $actual_mail['Text'];
		$actual_mail      = [
			'From'    => $actual_mail['From'],
			'To'      => $actual_mail['To'],
			'ReplyTo' => $actual_mail['ReplyTo'],
			'Subject' => $actual_mail['Subject'],
		];

		$expected_mail = [
			'From'    => [
				'Name'    => '',
				'Address' => 'wordpress@example.org',
			],
			'To'      => [
				[
					'Name'    => '',
					'Address' => 'sendto@test.com',
				],
			],
			'ReplyTo' => [],
			'Subject' => 'test subject',
		];
		$this->assertEquals( $expected_mail, $actual_mail );

		$this->assertStringContainsString( '<p>WP test-mail.php test (html)</p>', $actual_mail_html );

		// note: it is expected this is not '... (text)'
		$this->assertStringContainsString( 'WP test-mail.php test (html)', $actual_mail_text );
	}

	private function is_wp_mail_smtp_setup() {
		return defined( 'WPMS_ON' ) && WPMS_ON;
	}

	private function get_caught_mail() {
		$url     = 'http://' . WPMS_SMTP_HOST . ':8025/api/v1/message/latest';
		$message = \Piwik\Http::sendHttpRequest( $url, 1 );
		return json_decode( $message, true );
	}

	public function manually_load_plugin() {
		$file = $this->plugin_file();
		if ( is_file( $file ) ) {
			require_once $file;

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			do_action( 'plugins_loaded' );
		}
	}

	private function plugin_file() {
		return ABSPATH . 'wp-content/plugins/wp-mail-smtp/wp_mail_smtp.php';
	}
}
