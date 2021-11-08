<?php

namespace WpMatomo\WpStatistics;

use DateTime as OriginalDateTime;

class DateTime extends OriginalDateTime {
	/**
	 * @return \DateTime
	 */
	public function beginDay() {
		$date_time = clone $this;
		$date_time->setTime( 0, 0, 0 );

		return $date_time;
	}

	public function endDay() {
		$date_time = clone $this;
		$date_time->setTime( 23, 59, 59 );

		return $date_time;
	}

	public function toWpsMySQL() {
		return $this->format( 'Y-m-d' );
	}
}