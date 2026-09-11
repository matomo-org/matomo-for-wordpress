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
use Piwik\Plugins\TagManager\Template\Tag\CustomHtmlTag;
use Piwik\Plugins\TagManager\Template\Tag\CustomImageTag;
use Piwik\Plugins\TagManager\Template\Tag\LivezillaDynamicTag;
use Piwik\Plugins\TagManager\Template\Tag\TagsProvider;
use Piwik\Plugins\TagManager\Template\Variable\ConstantVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomJsFunctionVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomRequestProcessingVariable;
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
use Piwik\Plugins\TagManager\Template\Variable\VariablesProvider;
use WpMatomo\Roles;

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
		API::setSingletonInstance( $this->make_api_get_mock() );

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
		CoreAdminHomeAPI::setSingletonInstance( $this->make_coreadminhome_archive_mock() );

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

		$this->assert_is_wp_remote_mock_response( $result );

		// superuser access shouldn't leak out of the method
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_rejects_getbulkrequest_method_alone() {
		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=API.getBulkRequest&trigger=archivephp'
			. '&urls[]=method%3DAPI.getMatomoVersion';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assert_is_wp_remote_mock_response( $result );
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_onSendHttpRequestBy_rejects_encoded_duplicate_method_key_instead_of_bypassing_allowlist() {
		$url = 'http://localhost/wp-admin/admin.php?page=matomo-reporting'
			. '&module=API&method=API.getMatomoVersion&%6dethod=API.get&trigger=archivephp';

		$result = $this->invoke_onSendHttpRequestBy( $url );

		$this->assert_is_wp_remote_mock_response( $result );
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	public function test_filterTagManagerVariables_should_constrain_the_matomo_configuration_for_a_user_without_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$variables = [ new MatomoConfigurationVariable() ];
		$this->plugin->filterTagManagerVariables( $variables );

		$this->assert_narrowed( MatomoConfigurationVariable::class, $variables[0] );
		$this->assertSame( MatomoConfigurationVariable::ID, $variables[0]->getId() );
	}

	public function test_filterTagManagerVariables_should_leave_the_matomo_configuration_alone_for_a_user_with_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->markTestSkipped( 'this install does not grant administrators unfiltered_html.' );
		}

		$original  = new MatomoConfigurationVariable();
		$variables = [ $original ];
		$this->plugin->filterTagManagerVariables( $variables );

		$this->assertSame( $original, $variables[0] );
	}

	public function test_filterTagManagerVariables_should_be_registered_so_tag_manager_resolves_the_constrained_variable() {
		if ( ! Manager::getInstance()->isPluginActivated( 'TagManager' ) ) {
			$this->markTestSkipped( 'Tag Manager is not activated on this install.' );
		}

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );

		// a fresh provider, since getAllVariables() caches its result per instance
		$provider = \Piwik\Container\StaticContainer::getContainer()->make( VariablesProvider::class );
		$resolved = $provider->getVariable( MatomoConfigurationVariable::ID );

		$this->assert_narrowed( MatomoConfigurationVariable::class, $resolved );
	}

	public function test_filterTagManagerVariables_should_not_touch_any_other_variable() {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );

		$other     = new ConstantVariable();
		$variables = [ $other ];
		$this->plugin->filterTagManagerVariables( $variables );

		$this->assertSame( $other, $variables[0] );
	}

	public function constrained_variables_provider() {
		return [
			[ MatomoConfigurationVariable::class ],
			[ CustomJsFunctionVariable::class ],
			[ CustomRequestProcessingVariable::class ],
		];
	}

	/**
	 * @dataProvider constrained_variables_provider
	 */
	public function test_filterTagManagerVariables_should_constrain_every_variable_that_can_run_javascript( $upstream ) {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$variables = [ new $upstream() ];
		$this->plugin->filterTagManagerVariables( $variables );

		$this->assert_narrowed( $upstream, $variables[0] );
	}

	public function constrained_tags_provider() {
		return [
			[ CustomHtmlTag::class ],
			[ CustomImageTag::class ],
			[ LivezillaDynamicTag::class ],
		];
	}

	/**
	 * @dataProvider constrained_tags_provider
	 */
	public function test_filterTagManagerTags_should_constrain_every_tag_that_can_reach_another_origin( $upstream ) {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$tags = [ new $upstream() ];
		$this->plugin->filterTagManagerTags( $tags );

		$this->assert_narrowed( $upstream, $tags[0] );
	}

	/**
	 * @dataProvider constrained_tags_provider
	 */
	public function test_filterTagManagerTags_should_leave_tags_alone_for_a_user_with_unfiltered_html( $upstream ) {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->markTestSkipped( 'this install does not grant administrators unfiltered_html.' );
		}

		$original = new $upstream();
		$tags     = [ $original ];
		$this->plugin->filterTagManagerTags( $tags );

		$this->assertSame( $original, $tags[0] );
	}

	public function test_filterTagManagerTags_should_be_registered_so_tag_manager_resolves_the_constrained_tag() {
		if ( ! Manager::getInstance()->isPluginActivated( 'TagManager' ) ) {
			$this->markTestSkipped( 'Tag Manager is not activated on this install.' );
		}

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );

		// a fresh provider, since getAllTags() caches its result per instance
		$provider = \Piwik\Container\StaticContainer::getContainer()->make( TagsProvider::class );
		$resolved = $provider->getTag( ( new CustomHtmlTag() )->getId() );

		$this->assert_narrowed( CustomHtmlTag::class, $resolved );
	}

	/**
	 * @param string                                          $upstream
	 * @param \Piwik\Plugins\TagManager\Template\BaseTemplate $template
	 */
	private function assert_narrowed( $upstream, $template ) {
		$this->assertInstanceOf( $upstream, $template );
		$this->assertNotSame( $upstream, get_class( $template ) );
		$this->assertSame( ( new $upstream() )->getId(), $template->getId() );
	}

	private function make_api_get_mock() {
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

	private function make_coreadminhome_archive_mock() {
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

	private function assert_is_wp_remote_mock_response( $result ) {
		$this->assertNotSame( 200, $result['status'] );
		$this->assert_bulk_request_response_not_present( $result['response'] );
	}
}
