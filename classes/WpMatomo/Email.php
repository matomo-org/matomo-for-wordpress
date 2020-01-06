<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;
use \Zend_Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Email extends \Zend_Mail_Transport_Abstract {

	/**
	 * @var \WP_Error|null
	 */
	private $wpMailError;
	private $wpContentType = null;

	public function onError ($error) {
		$this->wpMailError = $error;
	}

	public function setContentType($contentType) {
		if (!empty($this->wpContentType)) {
			return $this->wpContentType;
		}

		return $contentType;
	}

	public function send(Zend_Mail $mail) {
		$this->wpMailError = null;
		$this->_mail = $mail;

		add_action( 'wp_mail_failed' , array($this, 'onError') );
		add_filter( 'wp_mail_content_type' , array($this, 'setContentType'));

		if ($mail->getBodyHtml(true)) {
			$content = $mail->getBodyHtml(true);
			$this->wpContentType = 'text/html';
		} else {
			$content = $mail->getBodyText(true);
			$this->wpContentType = 'text/plain';
		}

		$headers = $mail->getHeaders();
		if (isset($headers['Subject'])) {
			unset($headers['Subject']);
		}
		$this->_prepareHeaders($headers);

		$attachments = array();
		if ($mail->hasAttachments) {
			for ($i = 0; $i < $mail->getPartCount(); $i++) {
				$attachments[] = $mail->getPartContent($i);
			}
		}

		$success = wp_mail( $mail->getRecipients(), $mail->getSubject(), $content, $this->header );

		$this->wpContentType = null;

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

	public function _sendMail() {
		// TODO: Implement _sendMail() method.
	}

}
