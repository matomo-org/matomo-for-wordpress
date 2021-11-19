<?php

namespace WpMatomo\WpStatistics;

class Mapper {

	const PLATFORM = 'platform';
	const IP = 'ip';
	const HITS = 'hits';
	const AGENT = 'agent';
	const LOCATION = 'location';

	public static function getMap() {
		return [
			self::AGENT    => 'agent',
			self::HITS     => 'hits',
			self::IP       => 'ip',
			self::LOCATION => 'location',
			self::PLATFORM => 'platform'

		];

	}
}