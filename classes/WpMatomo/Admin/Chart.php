<?php
/**
 * @package matomo
 */
namespace WpMatomo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Chart {
	public function register_hooks() {
		add_action( 'matomo_load_chartjs', [ $this, 'load_chartjs' ] );
	}

	public function load_chartjs() {
		wp_enqueue_script( 'chart.js', "https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.4.1/chart.min.js", [], '1.0.0', true );
		wp_enqueue_script( 'matomo_chart.js', plugins_url( 'assets/chart.js', MATOMO_ANALYTICS_FILE ), [], '1.0.0', true );
	}
}
