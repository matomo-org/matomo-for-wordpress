<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Email extends \Zend_Mail_Transport_Abstract {

	/**
	 * @var \WP_Error|null
	 */
	private $wpMailError;

	public function onError ($error) {
		$this->wpMailError = $error;
	}

	public function setContentType($contentType) {
		if (!empty($this->_headers['Content-Type'][0])) {
			$content = trim( $this->_headers['Content-Type'][0] );
			if ( strpos( $content, ';' ) !== false ) {
				list( $type, $charset_content ) = explode( ';', $content );
				$type = trim( $type );
				if (!empty($type)) {
					$contentType = $type;
				}

			} elseif ( !empty($content) ) {
				$contentType = $content;
			}
		}

		return $contentType;
	}

	public function _sendMail() {
		$this->wpMailError = null;

		add_action( 'wp_mail_failed', array($this, 'onError') );
		add_filter('wp_mail_content_type', array($this, 'setContentType'));

		$success = wp_mail( $this->recipients, $this->_mail->getSubject(), $this->body, $this->header );

		remove_action( 'wp_mail_failed', array($this, 'onError') );
		remove_filter('wp_mail_content_type', array($this, 'setContentType'));

		if (!$success) {
			$message = 'Error unknown';
			if (!empty($this->wpMailError) && is_object($this->wpMailError) && $this->wpMailError instanceof \WP_Error) {
				$message = $this->wpMailError->get_error_message();
			}
			$logger = new Logger();
			$logger->log_exception('matomo_mail', new \Exception($message));
		}

		$this->wpMailError = null;
	}

}
