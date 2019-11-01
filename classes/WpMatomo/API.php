<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

use Piwik\API\Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class API {
	const VERSION = 'matomo/v1';

	const ROUTE_HIT = 'hit';

	public function register_hooks() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( self::VERSION, '/' . self::ROUTE_HIT . '/', array(
			'methods'  => array( 'GET', 'POST' ),
			'callback' => array( $this, 'hit' ),
		) );
		$this->register_route( 'API', 'getProcessedReport' );
		$this->register_route( 'API', 'getReportMetadata' );
		$this->register_route( 'API', 'getMatomoVersion' );
		$this->register_route( 'API', 'getMetadata' );
		$this->register_route( 'API', 'getSegmentsMetadata' );
		$this->register_route( 'API', 'getWidgetMetadata' );
		$this->register_route( 'API', 'getRowEvolution' );
		$this->register_route( 'API', 'getSuggestedValuesForSegment' );
		$this->register_route( 'API', 'getSettings' );
		$this->register_route( 'Annotations', 'add' );
		$this->register_route( 'Annotations', 'getAll' );
		$this->register_route( 'CoreAdminHome', 'invalidateArchivedReports' );
		$this->register_route( 'CoreAdminHome', 'runScheduledTasks' );
		$this->register_route( 'Dashboard', 'getDashboards' );
		$this->register_route( 'ImageGraph', 'get' );
		$this->register_route( 'LanguagesManager', 'getAvailableLanguages' );
		$this->register_route( 'LanguagesManager', 'getAvailableLanguagesInfo' );
		$this->register_route( 'LanguagesManager', 'getAvailableLanguageNames' );
		$this->register_route( 'LanguagesManager', 'getLanguageForUser' );
		$this->register_route( 'Live', 'getCounters' );
		$this->register_route( 'Live', 'getLastVisitsDetails' );
		$this->register_route( 'Live', 'getVisitorProfile' );
		$this->register_route( 'Live', 'getMostRecentVisitorId' );
		$this->register_route( 'PrivacyManager', 'deleteDataSubjects' );
		$this->register_route( 'PrivacyManager', 'exportDataSubjects' );
		$this->register_route( 'PrivacyManager', 'anonymizeSomeRawData' );
		$this->register_route( 'ScheduledReports', 'getReports' );
		$this->register_route( 'ScheduledReports', 'sendReport' );
		$this->register_route( 'SegmentEditor', 'add' );
		$this->register_route( 'SegmentEditor', 'update' );
		$this->register_route( 'SegmentEditor', 'delete' );
		$this->register_route( 'SegmentEditor', 'get' );
		$this->register_route( 'SegmentEditor', 'getAll' );
		$this->register_route( 'SitesManager', 'getAllSites' );
		$this->register_route( 'SitesManager', 'getAllSitesId' );
		$this->register_route( 'UsersManager', 'getUsers' );
		$this->register_route( 'UsersManager', 'getUsersLogin' );
		$this->register_route( 'UsersManager', 'getUser' );
		$this->register_route( 'Goals', 'getGoals' );

		// todo ideally we would make here work /goal/12345 to get goalId 12345
		$this->register_route( 'Goals', 'getGoal' );
		$this->register_route( 'Goals', 'addGoal' );
		$this->register_route( 'Goals', 'updateGoal' );
		$this->register_route( 'Goals', 'deleteGoal' );
	}

	public function hit() {
		if ( empty( $_GET ) && empty( $_POST ) && empty( $_POST['idsite'] ) && empty( $_GET['idsite'] ) ) {
			// todo if uploads dir is not writable, we may want to generate the matomo.js here and save it as an
			// option... then we could also save it compressed
			$paths = new Paths();
			$path  = $paths->get_matomo_js_upload_path();
			header( "Content-Type: application/javascript" );
			header( 'Content-Length: ' . ( filesize( $path ) ) );
			readfile( $paths->get_upload_base_dir() . '/matomo.js' ); // Reading the file into the output buffer
			exit;
		}
		include_once plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/piwik.php';
		exit;
	}

	public function execute_api_method( \WP_REST_Request $request ) {
		$attributes = $request->get_attributes();
		$method     = $attributes['matomoModule'] . '.' . $attributes['matomoMethod'];

		$with_idsite = true;

		return $this->execute_request( $method, $with_idsite, $request->get_params() );
	}

	/**
	 * @param string $method
	 *
	 * @return string
	 * @internal
	 * for tests only
	 */
	public function to_snake_case( $method ) {
		preg_match_all( '!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $method, $matches );

		$snake_case = $matches[0];

		foreach ( $snake_case as &$match ) {
			$match = $match == strtoupper( $match ) ? strtolower( $match ) : lcfirst( $match );
		}

		return implode( '_', $snake_case );
	}

	/**
	 * @api
	 */
	public function register_route( $api_module, $api_method ) {
		$methods                 = array(
			'get'        => 'GET',
			'edit'       => 'PUT',
			'update'     => 'PUT',
			'create'     => 'POST',
			'add'        => 'POST',
			'anonymize'  => 'POST',
			'invalidate' => 'POST',
			'run'        => 'POST',
			'send'       => 'POST',
			'delete'     => 'DELETE',
			'remove'     => 'DELETE'
		);
		$starts_with_keep_prefix = array( 'anonymize', 'invalidate', 'run', 'send' );

		$method        = 'GET';
		$wp_api_module = $this->to_snake_case( $api_module );
		$wp_api_action = $this->to_snake_case( $api_method );

		foreach ( $methods as $methodStartsWith => $methodToUse ) {
			if ( strpos( $api_method, $methodStartsWith ) === 0 ) {
				$method = $methodToUse;
				if ( ! in_array( $methodStartsWith, $starts_with_keep_prefix ) ) {
					$new_action = trim( ltrim( substr( $wp_api_action, strlen( $methodStartsWith ) ), '_' ) );
					if ( ! empty( $new_action ) ) {
						$wp_api_action = $new_action;
					}
				}
				break;
			}
		}

		register_rest_route( self::VERSION, '/' . $wp_api_module . '/' . $wp_api_action . '/', array(
			'methods'      => $method,
			'callback'     => array( $this, 'execute_api_method' ),
			'matomoModule' => $api_module,
			'matomoMethod' => $api_method,
		) );
	}

	private function execute_request( $api_method, $with_idsite, $params ) {
		if ( $with_idsite ) {
			$site   = new Site();
			$idsite = $site->get_current_matomo_site_id();

			if ( ! $idsite ) {
				return new \WP_Error( 'Site not found. Make sure it is synced' );
			}

			$params['idSite']  = $idsite;
			$params['idsite']  = $idsite;
			$params['idsites'] = $idsite;
			$params['idSites'] = $idsite;
		}

		// ensure user is authenticated through wordpress!
		unset( $_GET['token_auth'] );
		unset( $_POST['token_auth'] );

		Bootstrap::do_bootstrap();

		try {
			$result = Request::processRequest( $api_method, $params );
		} catch ( \Exception $e ) {
			$code = 'matomo_error';
			if ( $e->getCode() ) {
				$code .= '_' . $code;
			}
			if ( get_class( $e ) !== 'Exception' ) {
				$code = str_replace( 'piwik', 'matomo', $this->to_snake_case( get_class( $e ) ) );
			}

			return new \WP_Error( $code, $e->getMessage() );
		}

		return $result;
	}
}
