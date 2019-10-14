<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Email extends \Zend_Mail_Transport_Abstract {

	public function _sendMail() {
		wp_mail( $this->recipients, $this->_mail->getSubject(), $this->body, $this->header );
	}

}
