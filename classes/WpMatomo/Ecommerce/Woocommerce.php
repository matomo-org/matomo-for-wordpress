<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Ecommerce;

use WC_Order;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
if ( ! defined( 'MATOMO_WOOCOMMERCE_IGNORED_ORDER_STATUS' ) ) {
	// phpcs:ignore PHPCompatibility.InitialValue.NewConstantArraysUsingDefine.Found
	define( 'MATOMO_WOOCOMMERCE_IGNORED_ORDER_STATUS', [ 'cancelled', 'failed', 'refunded' ] );
}

class Woocommerce extends Base {
	private $order_status_ignore = MATOMO_WOOCOMMERCE_IGNORED_ORDER_STATUS;

	public function register_hooks() {
		if ( is_admin() ) {
			return;
		}

		parent::register_hooks();

		add_action( 'wp_head', [ $this, 'maybe_track_order_complete' ], 99999 );
		add_action( 'woocommerce_after_single_product', [ $this, 'on_product_view' ], 99999, $args = 0 );
		add_action( 'woocommerce_add_to_cart', [ $this, 'on_cart_updated_safe' ], 99999, 0 );
		add_action( 'woocommerce_cart_item_removed', [ $this, 'on_cart_updated_safe' ], 99999, 0 );
		add_action( 'woocommerce_cart_item_restored', [ $this, 'on_cart_updated_safe' ], 99999, 0 );
		add_action( 'woocommerce_cart_item_set_quantity', [ $this, 'on_cart_updated_safe' ], 99999, 0 );
		add_action( 'woocommerce_thankyou', [ $this, 'anonymise_orderid_in_url' ], 1, 1 );

		if ( ! $this->should_track_background() ) {
			// prevent possibly executing same event twice where eg first a PHP Matomo tracker request is created
			// because of woocommerce_applied_coupon and then also because of woocommerce_update_cart_action_cart_updated itself
			// causing two tracking requests to be issues from the server. refs #215
			// when not ajax mode the later event will simply overwrite the first and it should be fine.
			add_filter(
				'woocommerce_update_cart_action_cart_updated',
				[
					$this,
					'on_cart_updated_safe',
				],
				99999,
				1
			);
		}

		add_action( 'woocommerce_applied_coupon', [ $this, 'on_coupon_updated_safe' ], 99999, 0 );
		add_action( 'woocommerce_removed_coupon', [ $this, 'on_coupon_updated_safe' ], 99999, 0 );
	}

	public function anonymise_orderid_in_url( $order_id ) {
		if ( ! empty( $order_id ) && is_numeric( $order_id ) ) {
			$order_id = (int) $order_id;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<script>(function () {
	if (location.href) { 
		window._paq = window._paq || [];
	    var url = location.href;
		if (url.indexOf('?') > 0) { 
		    url = url.substr(0, url.indexOf('?')); // remove order key
		}
		window._paq.push(['setCustomUrl', url.replace('$order_id', 'orderid_anonymised')]);
	}
})()</script>";
		}
	}

	public function maybe_track_order_complete() {
		global $wp;

		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;
			if ( ! empty( $order_id ) && $order_id > 0 ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->on_order( $order_id );
			}
		}
	}

	public function on_coupon_updated_safe() {
		try {
			$val = null;
			$val = $this->on_cart_updated( $val, true );
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'woo_on_cart_update', $e );
		}

		return $val;
	}

	public function on_cart_updated_safe( $val = null ) {
		try {
			$val = $this->on_cart_updated( $val );
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'woo_on_cart_update', $e );
		}

		return $val;
	}

	/**
	 * @param null $val needed for woocommerce_update_cart_action_cart_updated filter
	 * @param bool $is_coupon_update set to true if cart was updated because of a coupon
	 *
	 * @return mixed
	 */
	public function on_cart_updated( $val = null, $is_coupon_update = false ) {
		global $woocommerce;

		/** @var \WC_Cart $cart */
		$cart = $woocommerce->cart;
		if ( ! $is_coupon_update ) {
			// can cause cart coupon not to be applied when WooCommerce Subscriptions is used.
			$cart->calculate_totals();
		}
		$cart_content = $cart->get_cart();

		$tracking_code = '';

		foreach ( $cart_content as $item ) {
			/** @var WC_Product $product */
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
				$order                = new WC_Order( null );
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

			$title          = $product->get_title();
			$categories     = $this->get_product_categories( $product );
			$quantity       = isset( $item['quantity'] ) ? $item['quantity'] : 0;
			$params         = [ 'addEcommerceItem', '' . $sku, $title, $categories, $price, $quantity ];
			$tracking_code .= $this->make_matomo_js_tracker_call( $params );
		}

		$total = 0;
		if ( ! empty( $cart->total ) ) {
			$total = $cart->total;
		} elseif ( ! empty( $cart->cart_contents_total ) ) {
			$total = $cart->cart_contents_total;
		}

		$tracking_code .= $this->make_matomo_js_tracker_call( [ 'trackEcommerceCartUpdate', $total ] );

		$this->cart_update_queue = $this->wrap_script( $tracking_code );
		$this->logger->log( 'Tracked ecommerce cart update: ' . $this->cart_update_queue );

		return $val;
	}

	public function on_order( $order_id ) {
		if ( $this->has_order_been_tracked_already( $order_id ) ) {
			$this->logger->log( sprintf( 'Ignoring already tracked order %d', $order_id ) );

			return '';
		}

		$this->logger->log( sprintf( 'Matomo new order %d', $order_id ) );

		$order = wc_get_order( $order_id );
		// @see https://github.com/matomo-org/matomo-for-wordpress/issues/514
		if ( ! $order ) {
			return;
		}
		$order_id_to_track = $order_id;
		if ( method_exists( $order, 'get_order_number' ) ) {
			$order_id_to_track = $order->get_order_number();
		}

		$order_status = $order->get_status();

		$this->logger->log( sprintf( 'Order %s with order number %s has status: %s', $order_id, $order_id_to_track, $order_status ) );

		if ( in_array( $order_status, $this->order_status_ignore, true ) ) {
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
					$params         = [
						'addEcommerceItem',
						'' . $product_details['sku'],
						$product_details['title'],
						$product_details['categories'],
						$product_details['price'],
						$product_details['quantity'],
					];
					$tracking_code .= $this->make_matomo_js_tracker_call( $params );
				}
			}
		}

		$params         = [
			'trackEcommerceOrder',
			'' . $order_id_to_track,
			$order->get_total(),
			round( $order->get_subtotal(), 2 ),
			$order->get_cart_tax(),
			$this->isWC3() ? $order->get_shipping_total() : $order->get_total_shipping(),
			$order->get_total_discount(),
		];
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
	 * @param WC_Product $product
	 */
	private function get_sku( $product ) {
		if ( $product && $product->get_sku() ) {
			return $product->get_sku();
		}

		return $this->get_product_id( $product );
	}

	/**
	 * @param WC_Product $product
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
	 * @param WC_Order $order
	 * @param $item
	 *
	 * @return mixed
	 */
	private function get_product_details( $order, $item ) {
		$product_or_variation = false;
		if ( $this->isWC3() && ! empty( $item ) && is_object( $item ) && method_exists( $item, 'get_product' ) && is_callable(
			[
				$item,
				'get_product',
			]
		) ) {
			$product_or_variation = $item->get_product();
		} elseif ( method_exists( $order, 'get_product_from_item' ) ) {
			// eg woocommerce 2.x
			$product_or_variation = $order->get_product_from_item( $item );
		}

		if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
			// woocommerce 3
			$product_id = $item->get_product_id();
		} elseif ( isset( $item['product_id'] ) ) {
			// woocommerce 2.x
			$product_id = $item['product_id'];
		} else {
			return;
		}

		$product = wc_get_product( $product_id );

		$pr         = $product_or_variation ? $product_or_variation : $product;
		$sku        = $this->get_sku( $pr );
		$price      = $order->get_item_total( $item );
		$title      = $product->get_title();
		$categories = $this->get_product_categories( $product );
		$quantity   = $item['qty'];

		return [
			'sku'        => $sku,
			'title'      => $title,
			'categories' => $categories,
			'quantity'   => $quantity,
			'price'      => $price,
		];
	}

	/**
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	private function get_product_categories( $product ) {
		$product_id = $this->get_product_id( $product );

		$category_terms = get_the_terms( $product_id, 'product_cat' );

		$categories = [];

		if ( is_wp_error( $category_terms ) ) {
			return $categories;
		}

		if ( ! empty( $category_terms ) ) {
			foreach ( $category_terms as $category ) {
				$categories[] = $category->name;
			}
		}

		$max_num_categories = 5;
		$categories         = array_unique( $categories );
		$categories         = array_slice( $categories, 0, $max_num_categories );

		return $categories;
	}

	public function on_product_view() {
		global $product;

		if ( empty( $product ) ) {
			return;
		}

		/** @var WC_Product $product */
		$params = [
			'setEcommerceView',
			$this->get_sku( $product ),
			$product->get_title(),
			$this->get_product_categories( $product ),
			$product->get_price(),
		];

		// we're not using wc_enqueue_js eg to prevent sometimes this code from being minified on some JS minifier plugins
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->wrap_script( $this->make_matomo_js_tracker_call( $params ) );
	}
}
