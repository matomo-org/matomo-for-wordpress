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

	public function onError($error) {
		$this->wpMailError = $error;
	}

	public function setContentType($contentType) {
		if (!empty($this->wpContentType)) {
			return $this->wpContentType;
		}

		return $contentType;
	}

	public function send(Zend_Mail $mail) {
		$this->wpContentType = null;
		$this->wpMailError = null;

		if ($mail->hasAttachments) {
			// we prefer sending the multipart message through _sendMail()
			// see #183 the problem is that we can't attach the raw attachment to WP_Mail which is why we need to rely
			// on the encoded message. This seems to work with built-in wp_mail but other custom implementations may have
			// issues which is why we are using plain text/body by default below when there are no attachments
			parent::send($mail);
			return;
		}

		// we prefer sending the plain text or html message
		$this->_mail = $mail;

		if ($mail->getBodyHtml() && $mail->getBodyHtml()->getRawContent()) {
			$content = $mail->getBodyHtml()->getRawContent();
			$this->wpContentType = 'text/html';
		} elseif ($mail->getBodyText()) {
			$content = $mail->getBodyText()->getRawContent();
			$this->wpContentType = 'text/plain';
		} else {
			// seems no content... lets use _sendMail
			parent::send($mail);
			return;
		}

		$headers = $mail->getHeaders();
		if (isset($headers['Subject'])) {
			unset($headers['Subject']);
		}
		$this->_prepareHeaders($headers);

		$this->sendMailThroughWordPress($mail->getRecipients(), $mail->getSubject(), $content, $this->header);
	}

	public function _sendMail() {

		if (!empty($this->_headers['Content-Type'][0])) {
			$content = trim( $this->_headers['Content-Type'][0] );
			if ( strpos( $content, ';' ) !== false ) {
				list( $type, $charset_content ) = explode( ';', $content );
				$type = trim( $type );
				if (!empty($type)) {
					$this->wpContentType = $type;
				}

			} elseif ( !empty($content) ) {
				$this->wpContentType = $content;
			}
		}

		// used when we are dealing with attachment... we send the raw multipart body which includes attachments
		$this->sendMailThroughWordPress($this->recipients, $this->_mail->getSubject(), $this->body, $this->header);
	}

	private function sendMailThroughWordPress($recipients, $subject, $content, $header) {

		$this->wpMailError = null;

		add_action( 'wp_mail_failed' , array($this, 'onError') );
		add_filter( 'wp_mail_content_type' , array($this, 'setContentType'));

		$success = wp_mail( $recipients, $subject, $content, $header );

		remove_action( 'wp_mail_failed', array($this, 'onError') );
		remove_filter('wp_mail_content_type', array($this, 'setContentType'));

		if (!$success) {
			$message = 'Error unknown.';
			if (!empty($this->wpMailError) && is_object($this->wpMailError) && $this->wpMailError instanceof \WP_Error) {
				$message = $this->wpMailError->get_error_message();
			}
			if ($this->_mail && $this->_mail->hasAttachments) {
				$message .= ' (has attachments)';
			}
			if ($this->wpContentType) {
				$message .= ' (type '. $this->wpContentType .')';
			}
			$logger = new Logger();
			$logger->log_exception('matomo_mail', new \Exception($message));
		}

		$this->wpContentType = null;
		$this->wpMailError = null;
	}

}
