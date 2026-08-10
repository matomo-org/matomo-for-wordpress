<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Access;
use Piwik\DataTable;
use Piwik\Plugin\Manager;
use Piwik\Plugins\API\API;
use Piwik\Plugins\CoreAdminHome\API as CoreAdminHomeAPI;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class WordPressTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var \Piwik\Plugins\WordPress\WordPress
	 */
	private $plugin;

	public function setUp(): void {
		parent::setUp();

		$this->plugin = Manager::getInstance()->getLoadedPlugin( 'WordPress' );
		$this->assertNotEmpty( $this->plugin, 'WordPress plugin must be loaded to test its HTTP hook.' );

		// short-circuit every outbound HTTP request for non-archiving requests.
		// (onSendHttpRequestBy uses wp_remote_request() when the request doesn't look
		// like an archiving request).
		add_filter( 'pre_http_request', [ $this, 'stub_http_response' ], 10, 3 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', [ $this, 'stub_http_response' ] );

		parent::tearDown();
	}

	/**
	 * pre_http_request filter: returns a canned non-200 response so rejected URLs never
	 * reach the network.
	 */
	public function stub_http_response( $preempt, $parsed_args, $url ) {
		return [
			'headers'  => [],
			'body'     => '',
			'response' => [
				'code'    => 503,
				'message' => 'stubbed for test',
			],
		];
	}

	public function test_onSendHttpRequestBy_should_dispatch_legitimate_api_get_archive_url() {
		// short circuit API.get to skip archiving (slow and uneeded for this test)
		API::setSingletonInstance( $this->make_api_get_stub() );

		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting&format=json'
			. '&module=API&method=API.get&idSite=1&period=day&date=today&trigger=archivephp';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assertSame( 200, $result['status'] );
		$this->assertEquals( '[]', $result['response'] );

		// superuser access shouldn't leak out of the method
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_should_dispatch_legitimate_coreadminhome_archive_reports_url() {
		// short circuit CoreAdminHome.archiveReports to skip archiving (slow and uneeded for this test)
		CoreAdminHomeAPI::setSingletonInstance( $this->make_coreadminhome_archive_stub() );

		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=CoreAdminHome.archiveReports&idSite=1&period=day&date=today&trigger=archivephp';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assertSame( 200, $result['status'] );
		$this->assertNotEmpty( $result['response'] );

		// superuser access shouldn't leak out of the method
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_should_not_dispatch_crafted_duplicate_method_url_as_super_user() {
		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=API.get&trigger=archivephp'
			. '&method=API.getBulkRequest&urls[]=method%3DAPI.getMatomoVersion';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assert_is_wp_remote_stub_response( $result );

		// superuser access shouldn't leak out of the method
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_rejects_getbulkrequest_method_alone() {
		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=API.getBulkRequest&trigger=archivephp'
			. '&urls[]=method%3DAPI.getMatomoVersion';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assert_is_wp_remote_stub_response( $result );
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_rejects_encoded_duplicate_method_key_instead_of_bypassing_allowlist() {
		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=API.getMatomoVersion&%6dethod=API.get&trigger=archivephp';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assert_is_wp_remote_stub_response( $result );
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	private function make_api_get_stub() {
		$container = \Piwik\Container\StaticContainer::getContainer();
		return new class(
			$container->get( \Piwik\Plugin\SettingsProvider::class ),
			$container->get( \Piwik\Plugins\API\ProcessedReport::class )
		) extends API {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			public function get( $idSite, $period, $date, $segment = \false, $columns = \false ) {
				return new DataTable();
			}
		};
	}

	private function make_coreadminhome_archive_stub() {
		$container = \Piwik\Container\StaticContainer::getContainer();
		return new class(
			$container->get( \Piwik\Scheduler\Scheduler::class ),
			$container->get( \Piwik\Archive\ArchiveInvalidator::class ),
			$container->get( \Piwik\Tracker\Failures::class ),
			$container->get( \Piwik\Plugins\CoreAdminHome\OptOutManager::class )
		) extends CoreAdminHomeAPI {
			// phpcs:ignore PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.intFound, WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
			public function archiveReports( int $idSite, string $period, string $date, $segment = \false, $plugin = \false, $report = \false ) {
				return [
					'idarchives' => [],
					'nb_visits'  => 0,
				];
			}
		};
	}

	private function invoke_onSendHttpRequestBy( $url ) {
		$response = null;
		$status   = null;
		$headers  = null;

		// full $params so the generic HTTP fall-through does not raise undefined-index
		// warnings (the test error handler turns those into exceptions).
		$params = [
			'headers'         => [],
			'httpMethod'      => 'GET',
			'timeout'         => 1,
			'body'            => '',
			'userAgent'       => null,
			'destinationPath' => null,
		];

		$threw = null;
		try {
			$this->plugin->onSendHttpRequestBy( $url, $params, $response, $status, $headers );
		} catch ( Exception $e ) {
			// rejected URLs fall through to wp_remote_request which is expected to fail
			// (it points at a closed port).
			$threw = $e;
		}

		return [
			'response'  => $response,
			'status'    => $status,
			'headers'   => $headers,
			'exception' => $threw,
		];
	}

	private function assert_bulk_request_response_not_present( $response ) {
		if ( null === $response || '' === $response ) {
			$this->assertTrue( true );
			return;
		}

		$this->assertStringNotContainsString( '<row>', $response );
		$this->assertStringNotContainsString( '<result>', $response );
		$this->assertStringNotContainsString( '"result"', $response );
	}

	private function assert_is_wp_remote_stub_response( $result ) {
		$this->assertNotSame( 200, $result['status'] );
		$this->assert_bulk_request_response_not_present( $result['response'] );
	}
}
