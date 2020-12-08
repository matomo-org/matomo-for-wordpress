<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;
use PHPMailer\PHPMailer\PHPMailer;
use Piwik\Common;
use Piwik\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Email {

	/**
	 * @var \WP_Error|null
	 */
	private $wpMailError;

	private $wpContentType = null;
    /**
     * @var Mail
     */
	private $mail;

	public function onError($error) {
		$this->wpMailError = $error;
	}

	public function setContentType($contentType) {
		if (!empty($this->wpContentType)) {
			return $this->wpContentType;
		}

		return $contentType;
	}

    public function send(Mail $mail)
    {
		$this->wpContentType = null;
		$this->wpMailError = null;

		$this->mail = $mail;

		if ($mail->getBodyHtml()) {
			$content = $mail->getBodyHtml();
			$this->wpContentType = 'text/html';
		} elseif ($mail->getBodyText()) {
			$content = $mail->getBodyText();
			$this->wpContentType = 'text/plain';
		} else {
			// seems no content...
            $content = '';
		}

        $attachments = $mail->getAttachments();

		$recipients = array_keys($mail->getRecipients());

		$this->sendMailThroughWordPress($recipients, $mail->getSubject(), $content, $attachments);
	}

	private function rememberMailSent(){

		$history = \WpMatomo::$settings->get_global_option( 'mail_history' );
		if ( empty( $history ) || ! is_array( $history ) ) {
			$history = array();
		}

		// allows us to see if there is a WP Mail issue or a Matomo issue
		array_unshift( $history, gmdate( 'Y-m-d H:i:s', time() ) );
		$history = array_slice( $history, 0, 3 ); // keep only the last 3 versions
		\WpMatomo::$settings->set_global_option( 'mail_history', $history );
		\WpMatomo::$settings->save();
	}

	private function sendMailThroughWordPress($recipients, $subject, $content, $attachments) {

		$this->wpMailError = null;

		add_action( 'wp_mail_failed' , array($this, 'onError') );
		add_filter( 'wp_mail_content_type' , array($this, 'setContentType'));

		$this->rememberMailSent();

		$header = '';

		if (!empty($attachments)) {

            $random_id = Common::generateUniqId();
            $header = 'X-Matomo: ' . $random_id;
            $executed_action = false;

            add_action('phpmailer_init', function ($phpmailer) use ($attachments, $subject, $random_id, &$executed_action) {
            	/** @var PHPMailer $phpmailer */
                if ($executed_action) {
                    return; // already done, do not execute another time
                }
                $executed_action = true;
                $match = false;
                foreach ($phpmailer->getCustomHeaders() as $header) {
                    if (isset($header[0]) && isset($header[1]) &&
                        is_string($header[0]) && is_string($header[1]) &&
                        Common::mb_strtolower($header[0]) === 'x-matomo' && $random_id === trim($header[1])) {
                        $match = true;
                    }
                }
                if (!$match) {
                    return; // attachments aren't for this mail
                }
                foreach ($attachments as $attachment) {
                    if (!empty($attachment['cid'])) {
                        $phpmailer->addStringEmbeddedImage(
                            $attachment['content'],
                            $attachment['cid'],
                            $attachment['filename'],
                            PHPMailer::ENCODING_BASE64,
                            $attachment['mimetype']
                        );
                    } else {
                        $phpmailer->addStringAttachment(
                            $attachment['content'],
                            $attachment['filename'],
                            PHPMailer::ENCODING_BASE64,
                            $attachment['mimetype']
                        );
                    }
                }
            });
        }

		$success = wp_mail( $recipients, $subject, $content, $header );

		remove_action( 'wp_mail_failed', array($this, 'onError') );
		remove_filter('wp_mail_content_type', array($this, 'setContentType'));

		if (!$success) {
			$message = 'Error unknown.';
			if (!empty($this->wpMailError) && is_object($this->wpMailError) && $this->wpMailError instanceof \WP_Error) {
				$message = $this->wpMailError->get_error_message();
			}
			if ($this->mail && $this->mail->getAttachments()) {
				$message .= ' (has attachments)';
			}
			if ($this->wpContentType) {
				$message .= ' (type '. $this->wpContentType .')';
			}
			$logger = new Logger();
			$logger->log_exception('mail_error', new \Exception($message));
			$logger->log('Matomo mail failed with subject '. $subject . ': ' . $message);
		}

		$this->wpContentType = null;
		$this->wpMailError = null;
	}

}
