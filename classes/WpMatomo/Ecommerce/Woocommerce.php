<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Ecommerce;

use WpMatomo\Logger;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Woocommerce extends Base {
	private $orderStatusIgnore = array( 'cancelled', 'failed', 'refunded' );

	public function register_hooks() {
		if ( is_admin() ) {
			return;
		}

		add_action( 'wp_head', array( $this, 'maybe_track_order_complete' ), 99999 );
		add_action( 'woocommerce_after_single_product', array( $this, 'on_product_view' ), 99999, $args = 0 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'on_cart_updated' ), 99999, 0 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'on_cart_updated' ), 99999, 0 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'on_cart_updated' ), 99999, 0 );
		add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'on_cart_updated' ), 99999, 1 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'on_cart_updated' ), 99999, 0 );
		add_action( 'woocommerce_removed_coupon', array( $this, 'on_cart_updated' ), 99999, 0 );
	}

	public function maybe_track_order_complete() {
		global $wp;

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
			if ( ! empty( $order_id ) && $order_id > 0 ) {
				echo $this->on_order( $order_id );
			}
		}
	}

	public function on_cart_updated() {
		global $woocommerce;

		/** @var \WC_Cart $cart */
		$cart = $woocommerce->cart;
		$cart->calculate_totals();
		$cart_content = $cart->get_cart();

		$tracking_code = '';

		foreach ( $cart_content as $item ) {
			/** @var \WC_Product $product */
			$product = wc_get_product( $item['product_id'] );

			if ( $this->isWC3() ) {
				$product_or_variation = $product;

				if ( ! empty( $item['variation_id'] ) ) {
					$variation = wc_get_product( $item['variation_id'] );
					if ( ! empty( $variation ) ) {
						$product_or_variation = $variation;
					}
				}
			} else {
				$order                = new \WC_Order( null );
				$product_or_variation = $order->get_product_from_item( $item );
			}

			if ( empty( $product_or_variation ) ) {
				continue;
			}

			$sku = $this->get_sku( $product_or_variation );

			$price = 0;
			if ( isset( $item['line_total'] ) ) {
				$total = floatval( $item['line_total'] ) / max( 1, $item['quantity'] );
				$price = round( $total, wc_get_price_decimals() );
			}

			$title         = $product->get_title();
			$categories    = $this->get_product_categories( $product );
			$quantity      = isset( $item['quantity'] ) ? $item['quantity'] : 0;
			$params        = array( 'addEcommerceItem', ''.$sku, $title, $categories, $price, $quantity );
			$tracking_code .= $this->make_matomo_js_tracker_call( $params );
		}

		$total = 0;
		if ( ! empty( $cart->total ) ) {
			$total = $cart->total;
		} elseif ( ! empty( $cart->cart_contents_total ) ) {
			$total = $cart->cart_contents_total;
		}

		$tracking_code .= $this->make_matomo_js_tracker_call( array( 'trackEcommerceCartUpdate', $total ) );

		$this->cart_update_queue = $this->wrap_script( $tracking_code );
		$this->logger->log( 'Tracked ecommerce cart update: ' . $this->cart_update_queue );
	}

	public function on_order( $order_id ) {
		if ( $this->has_order_been_tracked_already( $order_id ) ) {
			$this->logger->log( sprintf( 'Ignoring already tracked order %d', $order_id ) );

			return '';
		}

		$this->logger->log( sprintf( 'Matomo new order %d', $order_id ) );

		$order = wc_get_order( $order_id );

		$order_id_to_track = $order_id;
		if ( method_exists( $order, 'get_order_number' ) ) {
			$order_id_to_track = $order->get_order_number();
		}

		$order_status = $order->get_status();

		$this->logger->log( sprintf( 'Order %s with order number %s has status: %s', $order_id, $order_id_to_track, $order_status ) );

		if ( in_array( $order_status, $this->orderStatusIgnore, $strict = true ) ) {
			$this->logger->log( 'Ignoring ecommerce order ' . $order_id . ' becauses of status: ' . $order_status );

			return '';
		}

		$items = $order->get_items();

		$tracking_code = '';
		if ( $items ) {
			foreach ( $items as $item ) {
				/** @var \WC_Order_Item_Product $item */

				$product_details = $this->get_product_details( $order, $item );

				if ( ! empty( $product_details ) ) {
					$params        = array(
						'addEcommerceItem',
						'' . $product_details['sku'],
						$product_details['title'],
						$product_details['categories'],
						$product_details['price'],
						$product_details['quantity']
					);
					$tracking_code .= $this->make_matomo_js_tracker_call( $params );
				}

			}
		}

		$params        = array(
			'trackEcommerceOrder',
			'' . $order_id_to_track,
			$order->get_total(),
			round($order->get_subtotal(), 2),
			$order->get_cart_tax(),
			$this->isWC3() ? $order->get_shipping_total() : $order->get_total_shipping(),
			$order->get_total_discount()
		);
		$tracking_code .= $this->make_matomo_js_tracker_call( $params );

		$this->logger->log( sprintf( 'Tracked ecommerce order %s with number %s', $order_id, $order_id_to_track ) );

		$this->set_order_been_tracked( $order_id );

		return $this->wrap_script( $tracking_code );
	}

	private function isWC3() {
		global $woocommerce;
		$result = version_compare( $woocommerce->version, '3.0', '>=' );

		return $result;
	}

	/**
	 * @param \WC_Product $product
	 */
	private function get_sku( $product ) {
		if ( $product && $product->get_sku() ) {
			return $product->get_sku();
		}

		return $this->get_product_id( $product );
	}

	/**
	 * @param \WC_Product $product
	 */
	private function get_product_id( $product ) {
		if ( ! $product ) {
			return;
		}

		if ( $this->isWC3() ) {
			return $product->get_id();
		}

		return $product->id;
	}

	/**
	 * @param \WC_Order $order
	 * @param $item
	 *
	 * @return mixed
	 */
	private function get_product_details( $order, $item ) {
		$product_or_variation = false;
		if ( $this->isWC3() && ! empty( $item ) && is_object( $item ) && method_exists( $item, 'get_product' ) && is_callable( array(
				$item,
				'get_product'
			) ) ) {
			$product_or_variation = $item->get_product();
		} else if ( method_exists( $order, 'get_product_from_item' ) ) {
			// eg woocommerce 2.x
			$product_or_variation = $order->get_product_from_item( $item );
		}

		if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
			// woocommerce 3
			$product_id = $item->get_product_id();
		} else if ( isset( $item['product_id'] ) ) {
			// woocommerce 2.x
			$product_id = $item['product_id'];
		} else {
			return;
		}

		$product = wc_get_product( $product_id );

		$sku        = $this->get_sku( $product_or_variation ? $product_or_variation : $product );
		$price      = $order->get_item_total( $item );
		$title      = $product->get_title();
		$categories = $this->get_product_categories( $product );
		$quantity   = $item['qty'];

		return array(
			'sku'        => $sku,
			'title'      => $title,
			'categories' => $categories,
			'quantity'   => $quantity,
			'price'      => $price,
		);
	}

	/**
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	private function get_product_categories( $product ) {
		$productId = $this->get_product_id( $product );

		$categoryTerms = get_the_terms( $productId, 'product_cat' );

		$categories = array();

		if ( is_wp_error( $categoryTerms ) ) {
			return $categories;
		}

		if ( ! empty( $categoryTerms ) ) {
			foreach ( $categoryTerms as $category ) {
				$categories[] = $category->name;
			}
		}

		$maxNumCategories = 5;
		$categories       = array_unique( $categories );
		$categories       = array_slice( $categories, 0, $maxNumCategories );

		return $categories;
	}

	public function on_product_view() {
		global $product;

		if ( empty( $product ) ) {
			return;
		}

		/** @var \WC_Product $product */
		$params = array(
			'setEcommerceView',
			$this->get_sku( $product ),
			$product->get_title(),
			$this->get_product_categories( $product ),
			$product->get_price()
		);

		// we're not using wc_enqueue_js eg to prevent sometimes this code from being minified on some JS minifier plugins
		echo $this->wrap_script( $this->make_matomo_js_tracker_call( $params ) );
	}

}
