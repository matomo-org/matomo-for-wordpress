<?php

namespace WpMatomo\WpStatistics\Logger;

interface LoggerInterface {
	public function debug( $message );

	public function info( $message );

	public function notice( $message );

	public function warning( $message );

	public function error( $message );

	public function fatal( $message );

	public function log( $message, $level );

	public function success( $message );

}