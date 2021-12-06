<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use DeviceDetector\Parser\Client\Browser;
use DeviceDetector\Parser\OperatingSystem;
use Piwik\Common;
use Piwik\Plugins\DevicesDetection\Archiver;
use WP_STATISTICS\MetaBox\browsers;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\BrowsersConverter;
use WpMatomo\WpStatistics\DataConverters\PlatformConverter;

class DeviceDetectionImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'DevicesDetection';

	public function importRecords( Date $date ) {
		$this->importBrowsers( $date );
		$this->importPlateform( $date );
	}

	/**
	 * @param Date $date
	 *
	 * @throws \Exception
	 */
	private function importBrowsers( Date $date ) {
		$devices = $this->getVisitors( $date );
		if ( array_key_exists( 'no_data', $devices ) && $devices['no_data'] ) {
			$devices = array();
		}
		$this->convertBrowsersInMatomo( $devices );
		$devices = BrowsersConverter::convert( $devices );
		$this->logger->debug( 'Import {nb_browsers} browsers...', [ 'nb_browsers' => $devices->getRowsCount() ] );
		$this->insertRecord( Archiver::BROWSER_RECORD_NAME, $devices );
		Common::destroy( $devices );
	}

	private function convertPlatformsInMatomo( &$platforms ) {
		// convert codification
		$platformIds   = array_keys( OperatingSystem::getAvailableOperatingSystems() );
		$platformNames = array_values( OperatingSystem::getAvailableOperatingSystems() );
		// we do not have the version with wpstatistics, so set an empty version
		array_walk(
			$platformIds,
			function( &$item1, $key ) {
				$item1 = $item1 . ';';
			}
		);
		$platformIds   = array_merge( $platformIds, [ 'MAC;OS X' ] );
		$platformNames = array_merge( $platformNames, [ 'OS X' ] );
		foreach ( $platforms as $id => $platform ) {
			if ( in_array( $platform['platform'], $platformNames ) ) {
				$platforms[ $id ]['platform'] = str_replace( $platformNames, $platformIds, $platform['platform'] );
			} else {
				$platforms[ $id ]['platform'] = 'UNK;UNK';
			}
		}
	}
	private function convertBrowsersInMatomo( &$devices ) {
		// convert codification
		$deviceIds   = array_keys( Browser::getAvailableBrowsers() );
		$deviceNames = array_values( Browser::getAvailableBrowsers() );
		// we do not have the version with wpstatistics, so set an empty version
		$deviceIds   = array_merge( [ '', '', 'FM', 'MS', 'SB', 'IM' ], $deviceIds );
		$deviceNames = array_merge( [ 'Microsoft Office', 'Unknown', 'Firefox Mobile', 'Silk', 'Samsung Internet', 'Mobile Internet Explorer' ], $deviceNames );
		foreach ( $devices as $id => $device ) {
			if ( in_array( $device['browser']['name'], $deviceNames ) ) {
				$devices[ $id ]['browser']['name'] = str_replace( $deviceNames, $deviceIds, $device['browser']['name'] );
			}
		}
	}
	/**
	 * @param Date $date
	 */
	private function importPlateform( Date $date ) {
		$platforms = $this->getVisitors( $date );
		$this->convertPlatformsInMatomo( $platforms );
		$platforms = PlatformConverter::convert( $platforms );
		$this->logger->debug( 'Import {nb_platform} platforms...', [ 'nb_platform' => $platforms->getRowsCount() ] );
		$this->insertRecord( Archiver::OS_VERSION_RECORD_NAME, $platforms );
		Common::destroy( $platforms );
	}
}
