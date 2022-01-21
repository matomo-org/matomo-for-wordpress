<?php

namespace WpMatomo\WpStatistics\Logger;

use Psr\Log\LoggerInterface;

/**
 * WP_CLi logger
 *
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class EchoLogger implements LoggerInterface {

	public function debug( $message, array $context = array() ) {
		echo(
			'Matomo:[import][debug] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function error( $message, array $context = array() ) {
		echo(
			'Matomo:[import][error] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function critical( $message, array $context = array() ) {
		echo(
			'Matomo:[import][critical] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function warning( $message, array $context = array() ) {
		echo(
			'Matomo:[import][warning] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function info( $message, array $context = array() ) {
		echo(
			'Matomo:[import][info] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function log( $level, $message, array $context = array() ) {
		echo(
			'Matomo:[import][' . $level . '] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function notice( $message, array $context = array() ) {
		echo(
			'Matomo:[import][notice] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function alert( $message, array $context = array() ) {
		echo(
			'Matomo:[import][alert] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function emergency( $message, array $context = array() ) {
		echo(
			'Matomo:[import][emergency] ' . str_replace(
				array_map(
					[
						$this,
						'getContext',
					],
					array_keys( $context )
				),
				array_values( $context ),
				$message
			)
		);
	}

	public function getContext( $context ) {
		return '{' . $context . '}';
	}
}
