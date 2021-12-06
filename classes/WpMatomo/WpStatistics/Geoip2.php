<?php

namespace WpMatomo\WpStatistics;

use GeoIp2\Database\Reader;
use WpMatomo\Paths;

/**
 * GeoIP2 client for matomo.
 *
 * Matomo expect a specific format for the location data, this is what this class does
 */
class Geoip2 {

	private static $instance = null;

	private $geoip;

	protected static $records = array();

	private function __construct() {
		$wpstatisticDatabase = WP_CONTENT_DIR . '/uploads/wp-statistics/GeoLite2-City.mmdb';
		if ( file_exists( $wpstatisticDatabase ) ) {
			$this->geoip = new Reader( $wpstatisticDatabase );
		} else {
			$paths       = new Paths();
			$this->geoip = new Reader( $paths->get_upload_base_dir() . '/DBIP-City.mmdb' );
		}
	}

	/**
	 * @return Geoip2
	 */
	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @param $ip
	 *
	 * @return \GeoIp2\Model\City|mixed
	 * @throws \GeoIp2\Exception\AddressNotFoundException
	 * @throws \MaxMind\Db\Reader\InvalidDatabaseException
	 */
	private function getRecord( $ip ) {
		if ( ! array_key_exists( $ip, self::$records ) ) {
			self::$records[ $ip ] = $this->geoip->city( $ip );
		}
		return self::$records[ $ip ];
	}

	public function getMatomoCountryCode( $ip ) {
		try {
			$record = $this->getRecord( $ip );
			return strtolower( $record->country->isoCode );
		} catch ( \Exception $e ) {
			return 'us';
		}
	}

	/**
	 * @param string $ip
	 * @param string $region
	 * @return string
	 */
	public function getMatomoRegionCode( $ip, $region ) {
		try {
			$record     = $this->getRecord( $ip );
			$regionCode = $record->mostSpecificSubdivision->isoCode;
			if ( empty( $regionCode ) ) {
				$regions = include dirname( MATOMO_ANALYTICS_FILE ) . '/app/plugins/GeoIp2/data/isoRegionNames.php';
				if ( array_key_exists( $record->country->isoCode, $regions ) ) {
					$regionsCode = array_flip( $regions[ $record->country->isoCode ] );
					if ( array_key_exists( $record->mostSpecificSubdivision->name, $regionsCode ) ) {
						$regionCode = $regionsCode[ $record->mostSpecificSubdivision->name ];
					}
					if ( empty( $regionCode ) ) {
						if ( array_key_exists( $region, $regionsCode ) ) {
							$regionCode = $regionsCode[ $region ];
						}
					}
				}
			}
			return $regionCode . '|' . $this->getMatomoCountryCode( $ip );
		} catch ( \Exception $e ) {
			return '|us';
		}
	}

	public function getMatomoCityCode( $ip, $region ) {
		try {
			$record = $this->getRecord( $ip );
			return $record->city->name . '|' . $this->getMatomoRegionCode( $ip, $region ) . '|' . $record->location->latitude . '|' . $record->location->longitude;
		} catch ( \Exception $e ) {
			return '||us';
		}
	}
}
