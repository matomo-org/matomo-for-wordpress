<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Ecommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class MemberPress extends Base {

	public function register_hooks() {
		if ( ! is_admin() ) {
			add_action( 'template_redirect', array( $this, 'on_product_view' ), 99999, 0 );
			add_action( 'wp_footer', array( $this, 'on_order' ), 99999, 2 );
			add_action( 'mepr-signup', array( $this, 'on_cart_update' ), 99999, 1 );
		}
	}

	/**
	 * @param \MeprTransaction $transaction
	 */
	public function on_cart_update( $transaction ) {
		$tracking_code  = '';
		$sku            = $transaction->id;
		$product        = $transaction->product();
		$params         = array(
			'addEcommerceItem',
			$sku,
			$product->post_title,
			$categories = array(),
			$transaction->amount,
			1,
		);
		$tracking_code .= $this->make_matomo_js_tracker_call( $params );

		$total          = $transaction->total;
		$tracking_code .= $this->make_matomo_js_tracker_call( array( 'trackEcommerceCartUpdate', $total ) );

		// we can't echo directly as we wouldn't know where in the template rendering stage we are and whether
		// we're supposed to print or not etc
		$this->cart_update_queue = $this->wrap_script( $tracking_code );
		$this->logger->log( 'Tracked ecommerce cart update: ' );
	}

	public function on_product_view() {
		if ( ! is_singular( 'memberpressproduct' ) ) {
			return;
		}

		$product_id = get_the_ID();

		if ( empty( $product_id ) ) {
			return;
		}

		if ( ! class_exists( '\MeprProduct' ) ) {
			return;
		}

		$product = new \MeprProduct( $product_id );

		$sku = $product_id;

		$params = array(
			'setEcommerceView',
			'' . $sku,
			$product->post_title,
			$categories = array(),
			$product->price,
		);

		echo $this->wrap_script( $this->make_matomo_js_tracker_call( $params ) );
	}

	public function on_order() {
		if ( isset( $_GET['membership'] )
			 && isset( $_GET['trans_num'] )
			 && class_exists( '\MeprTransaction' ) ) {
			$txn = \MeprTransaction::get_one_by_trans_num( (int) $_GET['trans_num'] );
			if ( isset( $txn->id ) && $txn->id > 0 ) {
				if ( $this->has_order_been_tracked_already( $txn->id ) ) {
					return;
				}
				$this->set_order_been_tracked( $txn->id );
				$transaction       = new \MeprTransaction( $txn->id );
				$order_id_to_track = $txn->trans_num;
				$product           = $transaction->product();

				$discount = 0;

				if ( $product && $transaction->coupon() ) {
					$discount = $product->price - $txn->amount;
				}
				$tracking_code  = '';
				$params         = array(
					'addEcommerceItem',
					'' . $product->ID,
					$product->post_title,
					array(),
					$txn->amount,
					1,
				);
				$tracking_code .= $this->make_matomo_js_tracker_call( $params );
				$params         = array(
					'trackEcommerceOrder',
					'' . $order_id_to_track,
					$txn->total,
					$txn->amount,
					$txn->tax_amount,
					$shipping = 0,
					$discount,
				);
				$tracking_code .= $this->make_matomo_js_tracker_call( $params );

				echo $this->wrap_script( $tracking_code );
			}
		}
	}

}
