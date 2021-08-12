<?php

namespace WpMatomo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Chart {

	public function register_hooks() {
		add_action( 'load_chartjs' , array ( $this, 'load_chartjs' ) );
	}

	public function load_chartjs() {
		wp_enqueue_script('chart.js', plugin_dir_url(__FILE__). '/../../../../node_modules/chart.js/dist/chart.js' );
		wp_enqueue_script('matomo_chart.js', plugin_dir_url(__FILE__). '/../../../../assets/chart.js' );
	}
}