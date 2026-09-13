<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Access;
use Piwik\API\Request as MatomoApiRequest;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Plugin\Manager;
use Piwik\Plugins\API\API;
use Piwik\Plugins\CoreAdminHome\API as CoreAdminHomeAPI;
use Piwik\Plugins\TagManager\Context\WebContext;
use Piwik\Plugins\TagManager\Dao\TagsDao;
use Piwik\Plugins\TagManager\Dao\VariablesDao;
use Piwik\Plugins\TagManager\Model\Container as ContainerModel;
use Piwik\Plugins\TagManager\Model\Tag as TagModel;
use Piwik\Plugins\TagManager\Model\Variable as VariableModel;
use Piwik\Plugins\TagManager\Template\Tag\CustomHtmlTag;
use Piwik\Plugins\TagManager\Template\Tag\CustomImageTag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleConsentModeV2Tag;
use Piwik\Plugins\TagManager\Template\Tag\HotjarTag;
use Piwik\Plugins\TagManager\Template\Tag\TagsProvider;
use Piwik\Plugins\TagManager\Template\Trigger\PageViewTrigger;
use Piwik\Plugins\TagManager\Template\Variable\ConstantVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomJsFunctionVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomRequestProcessingVariable;
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
use Piwik\Plugins\TagManager\Template\Variable\VariablesProvider;
use Piwik\Validators\Exception as ValidatorException;
use WpMatomo\Roles;
use WpMatomo\Site;

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

	public function test_filterTagManagerTags_should_not_prevent_versioning_a_container_that_already_uses_a_constrained_tag() {
		$this->require_tag_manager();

		$id_site  = $this->create_a_user_without_unfiltered_html();
		$tag_name = 'a custom html tag somebody else added';

		// note: Matomo access is not relevant here: the user is the super user of their blog's Matomo,
		// what they do not have is the WordPress capability to write arbitrary HTML
		Access::doAsSuperUser(
			function () use ( $id_site, $tag_name ) {
				$container = $this->create_a_container( $id_site );
				$this->store_a_custom_html_tag( $id_site, $container, $tag_name );

				// createContainerVersion() does not copy rows, it exports the draft and imports it
				// again through the API, so every parameter is offered to its template a second time
				$id_version = MatomoApiRequest::processRequest(
					'TagManager.createContainerVersion',
					[
						'idSite'      => $id_site,
						'idContainer' => $container['id_container'],
						'name'        => '0.2.0',
					]
				);

				$tags = MatomoApiRequest::processRequest(
					'TagManager.getContainerTags',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $id_version,
					]
				);

				$this->assertContains( $tag_name, wp_list_pluck( $tags, 'name' ) );
			}
		);
	}

	public function test_filterTagManagerTags_should_constrain_a_new_tag_again_once_a_version_has_been_created() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$this->store_a_custom_html_tag( $id_site, $container, 'a custom html tag somebody else added' );

				MatomoApiRequest::processRequest(
					'TagManager.createContainerVersion',
					[
						'idSite'      => $id_site,
						'idContainer' => $container['id_container'],
						'name'        => '0.2.0',
					]
				);

				$this->expectException( ValidatorException::class );

				MatomoApiRequest::processRequest(
					'TagManager.addContainerTag',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $this->get_draft_version_of( $id_site, $container['id_container'] ),
						'type'               => ( new CustomHtmlTag() )->getId(),
						'name'               => 'one this user is authoring',
						'parameters'         => [ 'customHtml' => '<script>alert(1)</script>' ],
					]
				);
			}
		);
	}

	public function test_filterTagManagerTags_should_refuse_copying_a_constrained_tag() {
		// unlike creating a version, a copy puts the script somewhere it was not before, so this
		// one is deliberately refused
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$id_tag    = $this->store_a_custom_html_tag( $id_site, $container, 'a custom html tag somebody else added' );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'not allowed to add HTML or JavaScript' );

				StaticContainer::get( TagModel::class )->copyTag( $id_site, $container['id_version'], $id_tag );
			}
		);
	}

	public function test_filterTagManagerVariables_should_refuse_copying_a_constrained_variable() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container   = $this->create_a_container( $id_site );
				$id_variable = $this->store_a_custom_js_function_variable( $id_site, $container, 'a js function somebody else added' );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'not allowed to add HTML or JavaScript' );

				StaticContainer::get( VariableModel::class )->copyVariable( $id_site, $container['id_version'], $id_variable );
			}
		);
	}

	public function test_filterTagManagerTags_should_refuse_copying_a_container_that_uses_a_constrained_tag() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$this->store_a_custom_html_tag( $id_site, $container, 'a custom html tag somebody else added' );

				$container_model = StaticContainer::get( ContainerModel::class );
				$before          = count( $container_model->getContainers( $id_site ) );

				try {
					$container_model->copyContainer( $id_site, $container['id_container'] );
					$this->fail( 'copying a container that uses a Custom HTML tag has to be refused' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'not allowed to add HTML or JavaScript', $e->getMessage() );
				}

				// copyContainer() deletes what it had made before it rethrows, so a refused copy
				// leaves nothing behind
				$this->assertCount( $before, $container_model->getContainers( $id_site ) );
			}
		);
	}

	public function test_addContainerVariable_should_refuse_a_name_that_could_be_read_as_a_variable_reference() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'cannot contain' );

				$this->add_a_constant_variable( $id_site, $container, 'X}}https://evil.example/{{Y' );
			}
		);
	}

	public function test_updateContainerVariable_should_refuse_renaming_a_variable_to_something_that_could_be_read_as_a_reference() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container   = $this->create_a_container( $id_site );
				$id_variable = $this->add_a_constant_variable( $id_site, $container, 'MatomoHost' );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'cannot contain' );

				MatomoApiRequest::processRequest(
					'TagManager.updateContainerVariable',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $container['id_version'],
						'idVariable'         => $id_variable,
						'name'               => 'X}}https://evil.example/{{Y',
						'parameters'         => [ 'constantValue' => '/' ],
					]
				);
			}
		);
	}

	public function test_updateContainerVariable_should_rename_a_variable_a_constrained_tag_refers_to() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container   = $this->create_a_container( $id_site );
				$id_variable = $this->add_a_constant_variable( $id_site, $container, 'MatomoHost' );
				$tag_name    = 'a custom html tag somebody else added';

				$this->store_a_custom_html_tag( $id_site, $container, $tag_name, '<script>console.log("{{MatomoHost}}");</script>' );

				MatomoApiRequest::processRequest(
					'TagManager.updateContainerVariable',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $container['id_version'],
						'idVariable'         => $id_variable,
						'name'               => 'MatomoHostRenamed',
						'parameters'         => [ 'constantValue' => '/' ],
					]
				);

				$tag = $this->find_container_tag( $id_site, $container, $tag_name );

				$this->assertStringContainsString( '{{MatomoHostRenamed}}', $tag['parameters']['customHtml'] );
			}
		);
	}

	public function test_importContainerVersion_should_restore_the_draft_when_it_refuses_an_import_that_uses_a_constrained_tag() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a custom html tag somebody else added';

				$this->store_a_custom_html_tag( $id_site, $container, $tag_name );

				$exported = MatomoApiRequest::processRequest(
					'TagManager.exportContainerVersion',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $container['id_version'],
					]
				);

				try {
					MatomoApiRequest::processRequest(
						'TagManager.importContainerVersion',
						[
							'idSite'                   => $id_site,
							'idContainer'              => $container['id_container'],
							'exportedContainerVersion' => wp_json_encode( $exported ),
						]
					);
					$this->fail( 'importing a container that uses a Custom HTML tag has to be refused' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'not allowed to add HTML or JavaScript', $e->getMessage() );
				}

				$this->assertNotNull(
					$this->find_container_tag( $id_site, $container, $tag_name ),
					'the refused import has to leave the draft it deleted as it found it'
				);
			}
		);
	}

	public function test_addContainerTag_should_refuse_a_tag_that_loads_javascript_from_another_service() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'not allowed to choose which other services' );

				$this->add_a_tag( $id_site, $container, 'a hotjar tag', ( new HotjarTag() )->getId(), [ 'hjid' => '1234567' ] );
			}
		);
	}

	public function test_addContainerTag_should_allow_a_tag_that_loads_javascript_from_another_service_for_a_user_with_unfiltered_html() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_with_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag an administrator added';

				$this->add_a_tag( $id_site, $container, $tag_name, ( new HotjarTag() )->getId(), [ 'hjid' => '1234567' ] );

				$this->assertNotNull( $this->find_container_tag( $id_site, $container, $tag_name ) );
			}
		);
	}

	public function test_addContainerTag_should_allow_a_third_party_tag_that_neither_runs_what_its_account_holds_nor_reports_visitors() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a google consent signal';

				// this one names no account of its own: it loads nothing and pushes a consent
				// state onto the page's own data layer for whatever is already listening
				$this->add_a_tag( $id_site, $container, $tag_name, ( new GoogleConsentModeV2Tag() )->getId(), [ 'consentAction' => [ 'update' ] ] );

				$this->assertNotNull( $this->find_container_tag( $id_site, $container, $tag_name ) );
			}
		);
	}

	public function test_updateContainerTag_should_refuse_changing_a_tag_that_loads_javascript_from_another_service() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$id_tag    = $this->store_a_third_party_tag( $id_site, $container, 'a hotjar tag somebody else added' );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'not allowed to choose which other services' );

				// the account the recordings go to is a parameter, so changing one is as much a
				// decision about which third party this site talks to as adding it was
				MatomoApiRequest::processRequest(
					'TagManager.updateContainerTag',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $container['id_version'],
						'idTag'              => $id_tag,
						'name'               => 'a hotjar tag somebody else added',
						'parameters'         => [ 'hjid' => '7654321' ],
						'fireTriggerIds'     => [ $container['id_trigger'] ],
					]
				);
			}
		);
	}

	public function test_copyTag_should_refuse_a_tag_that_loads_javascript_from_another_service() {
		// a copy goes straight to the model rather than through the API, which is why the refusal
		// lives there
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$id_tag    = $this->store_a_third_party_tag( $id_site, $container, 'a hotjar tag somebody else added' );

				$this->expectException( ValidatorException::class );
				$this->expectExceptionMessage( 'not allowed to choose which other services' );

				StaticContainer::get( TagModel::class )->copyTag( $id_site, $container['id_version'], $id_tag );
			}
		);
	}

	public function test_getContainerTags_should_still_describe_a_tag_that_loads_javascript_from_another_service() {
		// the template stays registered on purpose. Container generation looks a stored tag's
		// template up by type and leaves out what it cannot find, so withdrawing the template would
		// quietly drop this tag from the container the next time anything regenerated it
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag somebody else added';

				$this->store_a_third_party_tag( $id_site, $container, $tag_name );

				$tag = $this->find_container_tag( $id_site, $container, $tag_name );

				$this->assertNotEmpty( $tag['typeMetadata'], 'Tag Manager has to still resolve the template this tag was stored with' );
			}
		);
	}

	public function test_createContainerVersion_should_not_be_prevented_by_a_tag_that_loads_javascript_from_another_service() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag somebody else added';

				$this->store_a_third_party_tag( $id_site, $container, $tag_name );

				// createContainerVersion() does not copy rows, it exports the draft and imports it
				// again through the API, so the tag is offered to addContainerTag() a second time
				$id_version = MatomoApiRequest::processRequest(
					'TagManager.createContainerVersion',
					[
						'idSite'      => $id_site,
						'idContainer' => $container['id_container'],
						'name'        => 'a version of what was already there',
					]
				);

				$versioned = [
					'id_container' => $container['id_container'],
					'id_version'   => $id_version,
				];

				$this->assertNotEmpty( $id_version );
				$this->assertNotNull( $this->find_container_tag( $id_site, $versioned, $tag_name ) );
			}
		);
	}

	public function test_importContainerVersion_should_still_validate_an_import_that_asks_to_be_treated_as_a_draft_restore() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a custom html tag somebody else added';

				$this->store_a_custom_html_tag( $id_site, $container, $tag_name );

				$exported = MatomoApiRequest::processRequest(
					'TagManager.exportContainerVersion',
					[
						'idSite'             => $id_site,
						'idContainer'        => $container['id_container'],
						'idContainerVersion' => $container['id_version'],
					]
				);

				try {
					MatomoApiRequest::processRequest(
						'TagManager.importContainerVersion',
						[
							'idSite'                   => $id_site,
							'idContainer'              => $container['id_container'],
							'exportedContainerVersion' => wp_json_encode( $exported ),
							'_isDraftRestoreCall'      => 1,
						]
					);
					$this->fail( 'asking directly for the draft restore treatment must not skip the checks' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'not allowed to add HTML or JavaScript', $e->getMessage() );
				}

				// and the rollback the refusal triggers still gets its suspension, so the draft the
				// import emptied is put back
				$this->assertNotNull(
					$this->find_container_tag( $id_site, $container, $tag_name ),
					'the refused import has to leave the draft it deleted as it found it'
				);
			}
		);
	}

	public function test_resumeContainerTag_should_refuse_turning_a_tag_that_loads_javascript_from_another_service_back_on() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag somebody else added';
				$id_tag    = $this->store_a_third_party_tag( $id_site, $container, $tag_name );

				$this->pause_a_tag( $id_site, $container, $id_tag );

				try {
					$this->resume_a_tag( $id_site, $container, $id_tag );
					$this->fail( 'turning a blocked tag back on has to be refused' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'turn it back on', $e->getMessage() );
				}

				$this->assertSame( 'paused', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_resumeContainerTag_should_refuse_turning_a_custom_html_tag_back_on() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a custom html tag somebody else added';
				$id_tag    = $this->store_a_custom_html_tag( $id_site, $container, $tag_name );

				$this->pause_a_tag( $id_site, $container, $id_tag );

				try {
					$this->resume_a_tag( $id_site, $container, $id_tag );
					$this->fail( 'turning a narrowed tag back on has to be refused' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'turn it back on', $e->getMessage() );
				}

				$this->assertSame( 'paused', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_resumeContainerTag_should_refuse_turning_a_custom_image_tag_pointing_at_another_site_back_on() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a tracking pixel somebody else added';
				$id_tag    = $this->store_a_custom_image_tag( $id_site, $container, $tag_name, 'https://evil.example/pixel.gif' );

				$this->pause_a_tag( $id_site, $container, $id_tag );

				try {
					$this->resume_a_tag( $id_site, $container, $id_tag );
					$this->fail( 'turning a tag holding a value the user may not write back on has to be refused' );
				} catch ( ValidatorException $e ) {
					$this->assertStringContainsString( 'turn it back on', $e->getMessage() );
				}

				$this->assertSame( 'paused', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_resumeContainerTag_should_turn_a_custom_image_tag_pointing_at_this_site_back_on() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a tracking pixel on this site';
				$id_tag    = $this->store_a_custom_image_tag( $id_site, $container, $tag_name, home_url( '/pixel.gif' ) );

				$this->pause_a_tag( $id_site, $container, $id_tag );
				$this->resume_a_tag( $id_site, $container, $id_tag );

				$this->assertSame( 'active', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_resumeContainerTag_should_turn_a_tag_the_user_could_have_added_themselves_back_on() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a google consent signal';
				$id_tag    = $this->add_a_tag( $id_site, $container, $tag_name, ( new GoogleConsentModeV2Tag() )->getId(), [ 'consentAction' => [ 'update' ] ] );

				$this->pause_a_tag( $id_site, $container, $id_tag );
				$this->resume_a_tag( $id_site, $container, $id_tag );

				$this->assertSame( 'active', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_resumeContainerTag_should_turn_a_tag_that_loads_javascript_from_another_service_back_on_for_a_user_with_unfiltered_html() {
		$this->require_tag_manager();

		$id_site = $this->create_a_user_with_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag';
				$id_tag    = $this->store_a_third_party_tag( $id_site, $container, $tag_name );

				$this->pause_a_tag( $id_site, $container, $id_tag );
				$this->resume_a_tag( $id_site, $container, $id_tag );

				$this->assertSame( 'active', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	public function test_pauseContainerTag_should_still_pause_a_tag_that_loads_javascript_from_another_service() {
		// pausing can only stop something being served, so it stays available to everybody
		$this->require_tag_manager();

		$id_site = $this->create_a_user_without_unfiltered_html();

		Access::doAsSuperUser(
			function () use ( $id_site ) {
				$container = $this->create_a_container( $id_site );
				$tag_name  = 'a hotjar tag somebody else added';
				$id_tag    = $this->store_a_third_party_tag( $id_site, $container, $tag_name );

				$this->pause_a_tag( $id_site, $container, $id_tag );

				$this->assertSame( 'paused', $this->find_container_tag( $id_site, $container, $tag_name )['status'] );
			}
		);
	}

	private function require_tag_manager() {
		if ( ! Manager::getInstance()->isPluginActivated( 'TagManager' ) ) {
			$this->markTestSkipped( 'Tag Manager is not activated on this install.' );
		}
	}

	/**
	 * @return int the Matomo site this blog maps to
	 */
	private function create_a_user_without_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$id_site = Site::get_matomo_site_id( get_current_blog_id() );
		$this->assertNotEmpty( $id_site, 'the fixture has to have mapped this blog to a Matomo site' );

		return $id_site;
	}

	/**
	 * @return int the Matomo site this blog maps to
	 */
	private function create_a_user_with_unfiltered_html() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$this->markTestSkipped( 'this install does not grant administrators unfiltered_html.' );
		}

		$id_site = Site::get_matomo_site_id( get_current_blog_id() );
		$this->assertNotEmpty( $id_site, 'the fixture has to have mapped this blog to a Matomo site' );

		return $id_site;
	}

	/**
	 * @param int $id_site
	 * @return array{id_container: string, id_version: int, id_trigger: int} an empty draft container
	 */
	private function create_a_container( $id_site ) {
		$id_container = MatomoApiRequest::processRequest(
			'TagManager.addContainer',
			[
				'idSite'  => $id_site,
				'context' => WebContext::ID,
				'name'    => 'a container that predates the constraint ' . uniqid(),
			]
		);

		$id_version = $this->get_draft_version_of( $id_site, $id_container );

		return [
			'id_container' => $id_container,
			'id_version'   => $id_version,
			'id_trigger'   => MatomoApiRequest::processRequest(
				'TagManager.addContainerTrigger',
				[
					'idSite'             => $id_site,
					'idContainer'        => $id_container,
					'idContainerVersion' => $id_version,
					'type'               => PageViewTrigger::ID,
					'name'               => 'every page view',
				]
			),
		];
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $tag_name
	 * @param string $html
	 * @return int the tag ID
	 */
	private function store_a_custom_html_tag( $id_site, $container, $tag_name, $html = '<script>console.log(1);</script>' ) {
		return StaticContainer::get( TagsDao::class )->createTag(
			$id_site,
			$container['id_version'],
			( new CustomHtmlTag() )->getId(),
			$tag_name,
			[
				'customHtml'   => $html,
				'htmlPosition' => 'bodyEnd',
			],
			[ $container['id_trigger'] ],
			[],
			TagModel::FIRE_LIMIT_UNLIMITED,
			0,
			999,
			null,
			null,
			Date::now()->getDatetime()
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $variable_name
	 * @return int the variable ID
	 * @see store_a_custom_html_tag() for why this goes in through the DAO
	 */
	private function store_a_custom_js_function_variable( $id_site, $container, $variable_name ) {
		return StaticContainer::get( VariablesDao::class )->createVariable(
			$id_site,
			$container['id_version'],
			( new CustomJsFunctionVariable() )->getId(),
			$variable_name,
			[ 'jsFunction' => 'function () { return document.cookie; }' ],
			'',
			[],
			Date::now()->getDatetime()
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $tag_name
	 * @param string $src
	 * @return int the tag ID
	 * @see store_a_custom_html_tag() for why this goes in through the DAO
	 */
	private function store_a_custom_image_tag( $id_site, $container, $tag_name, $src ) {
		return StaticContainer::get( TagsDao::class )->createTag(
			$id_site,
			$container['id_version'],
			( new CustomImageTag() )->getId(),
			$tag_name,
			[
				'customImageSrc'     => $src,
				'cacheBusterEnabled' => false,
			],
			[ $container['id_trigger'] ],
			[],
			TagModel::FIRE_LIMIT_UNLIMITED,
			0,
			999,
			null,
			null,
			Date::now()->getDatetime()
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $tag_name
	 * @return int the tag ID
	 */
	private function store_a_third_party_tag( $id_site, $container, $tag_name ) {
		return StaticContainer::get( TagsDao::class )->createTag(
			$id_site,
			$container['id_version'],
			( new HotjarTag() )->getId(),
			$tag_name,
			[
				'hjid' => '1234567',
				'hjsv' => 6,
			],
			[ $container['id_trigger'] ],
			[],
			TagModel::FIRE_LIMIT_UNLIMITED,
			0,
			999,
			null,
			null,
			Date::now()->getDatetime()
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container  what create_a_container() returned
	 * @param string $tag_name
	 * @param string $type
	 * @param array  $parameters
	 * @return int the tag ID
	 */
	private function add_a_tag( $id_site, $container, $tag_name, $type, $parameters ) {
		return MatomoApiRequest::processRequest(
			'TagManager.addContainerTag',
			[
				'idSite'             => $id_site,
				'idContainer'        => $container['id_container'],
				'idContainerVersion' => $container['id_version'],
				'type'               => $type,
				'name'               => $tag_name,
				'parameters'         => $parameters,
				'fireTriggerIds'     => [ $container['id_trigger'] ],
			]
		);
	}

	/**
	 * @param int   $id_site
	 * @param array $container what create_a_container() returned
	 * @param int   $id_tag
	 */
	private function pause_a_tag( $id_site, $container, $id_tag ) {
		MatomoApiRequest::processRequest(
			'TagManager.pauseContainerTag',
			[
				'idSite'             => $id_site,
				'idContainer'        => $container['id_container'],
				'idContainerVersion' => $container['id_version'],
				'idTag'              => $id_tag,
			]
		);
	}

	/**
	 * @param int   $id_site
	 * @param array $container what create_a_container() returned
	 * @param int   $id_tag
	 */
	private function resume_a_tag( $id_site, $container, $id_tag ) {
		MatomoApiRequest::processRequest(
			'TagManager.resumeContainerTag',
			[
				'idSite'             => $id_site,
				'idContainer'        => $container['id_container'],
				'idContainerVersion' => $container['id_version'],
				'idTag'              => $id_tag,
			]
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $variable_name
	 * @return int the variable ID
	 */
	private function add_a_constant_variable( $id_site, $container, $variable_name ) {
		return MatomoApiRequest::processRequest(
			'TagManager.addContainerVariable',
			[
				'idSite'             => $id_site,
				'idContainer'        => $container['id_container'],
				'idContainerVersion' => $container['id_version'],
				'type'               => ( new ConstantVariable() )->getId(),
				'name'               => $variable_name,
				'parameters'         => [ 'constantValue' => '/' ],
			]
		);
	}

	/**
	 * @param int    $id_site
	 * @param array  $container what create_a_container() returned
	 * @param string $tag_name
	 * @return array|null the tag as Tag Manager reports it, or null when the container has no such tag
	 */
	private function find_container_tag( $id_site, $container, $tag_name ) {
		$tags = MatomoApiRequest::processRequest(
			'TagManager.getContainerTags',
			[
				'idSite'             => $id_site,
				'idContainer'        => $container['id_container'],
				'idContainerVersion' => $container['id_version'],
			]
		);

		foreach ( $tags as $tag ) {
			if ( $tag['name'] === $tag_name ) {
				return $tag;
			}
		}

		return null;
	}

	/**
	 * @param int    $id_site
	 * @param string $id_container
	 * @return int
	 */
	private function get_draft_version_of( $id_site, $id_container ) {
		$container = MatomoApiRequest::processRequest(
			'TagManager.getContainer',
			[
				'idSite'      => $id_site,
				'idContainer' => $id_container,
			]
		);

		return (int) $container['draft']['idcontainerversion'];
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
