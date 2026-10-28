<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Option;
use Piwik\Plugin\Manager;
use Piwik\Plugins\TagManager\Context\WebContext;
use Piwik\Plugins\TagManager\Template\Tag\CustomHtmlTag;
use Piwik\Plugins\TagManager\Template\Tag\CustomImageTag;
use Piwik\Plugins\TagManager\Template\Variable\CustomJsFunctionVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomRequestProcessingVariable;
use Piwik\Plugins\TagManager\Template\Variable\JavaScriptVariable;
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
use Piwik\Plugins\WordPress\Overrides\TagManager\SecuredTemplateFactory;
use Piwik\SettingsPiwik;
use Piwik\Validators\Exception as ValidatorException;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class SecuredTemplateFactoryTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var SecuredTemplateFactory
	 */
	private $template_factory;

	/**
	 * Null until a test overwrites the option, since the fixture is only restored between classes.
	 *
	 * @var string|false|null
	 */
	private $previous_matomo_url_option;

	public function setUp(): void {
		parent::setUp();

		$this->template_factory = new SecuredTemplateFactory();
	}

	public function tearDown(): void {
		if ( isset( $this->previous_matomo_url_option ) ) {
			if ( false === $this->previous_matomo_url_option ) {
				Option::delete( SettingsPiwik::OPTION_PIWIK_URL );
			} else {
				Option::set( SettingsPiwik::OPTION_PIWIK_URL, $this->previous_matomo_url_option );
			}

			$this->previous_matomo_url_option = null;
		}

		parent::tearDown();
	}

	public function test_customHtmlTag_should_refuse_any_custom_html() {
		$tag = $this->template_factory->customHtmlTag( new CustomHtmlTag() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $tag, 'customHtml' )->setValue( '<script>alert(1)</script>' );
	}

	public function test_customHtmlTag_should_refuse_the_default_custom_html_too() {
		$parameter = $this->find_parameter( $this->template_factory->customHtmlTag( new CustomHtmlTag() ), 'customHtml' );

		$this->expectException( ValidatorException::class );

		$parameter->setValue( $parameter->getDefaultValue() );
	}

	public function test_customHtmlTag_should_leave_the_other_parameters_alone() {
		$parameter = $this->find_parameter( $this->template_factory->customHtmlTag( new CustomHtmlTag() ), 'htmlPosition' );
		$parameter->setValue( 'bodyEnd' );

		$this->assertSame( 'bodyEnd', $parameter->getValue() );
	}

	public function test_customHtmlTag_should_stand_in_for_the_template_it_narrows() {
		$this->assert_stands_in_for(
			$this->template_factory->customHtmlTag( new CustomHtmlTag() ),
			new CustomHtmlTag(),
			[ 'parameters' => [ 'customHtml' => '<b>hi</b>' ] ]
		);
	}

	public function test_customHtmlTag_should_delegate_to_the_instance_it_was_given() {
		$given = new class() extends CustomHtmlTag {
			// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
			public function getName() {
				return 'the instance that was passed in';
			}
		};

		$secured = $this->template_factory->customHtmlTag( $given );

		$this->assertSame( 'the instance that was passed in', $secured->getName() );
	}

	public function test_customImageTag_should_refuse_an_image_on_another_host() {
		$tag = $this->template_factory->customImageTag( new CustomImageTag() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $tag, 'customImageSrc' )->setValue( 'https://evil.example/pixel.gif' );
	}

	public function test_customImageTag_should_accept_an_image_on_this_site() {
		$url = home_url( '/wp-content/uploads/pixel.gif' );

		$parameter = $this->find_parameter( $this->template_factory->customImageTag( new CustomImageTag() ), 'customImageSrc' );
		$parameter->setValue( $url );

		$this->assertSame( $url, $parameter->getValue() );
	}

	public function test_customImageTag_should_keep_a_query_string_on_the_image_source() {
		// unlike the two parameters that become a script origin, nothing is appended to this one,
		// and a tracking pixel on this site legitimately takes parameters
		$url = home_url( '/pixel.gif?campaign=spring' );

		$parameter = $this->find_parameter( $this->template_factory->customImageTag( new CustomImageTag() ), 'customImageSrc' );
		$parameter->setValue( $url );

		$this->assertSame( $url, $parameter->getValue() );
	}

	public function test_customImageTag_should_leave_the_other_parameters_alone() {
		$parameter = $this->find_parameter( $this->template_factory->customImageTag( new CustomImageTag() ), 'cacheBusterEnabled' );
		$parameter->setValue( false );

		$this->assertFalse( $parameter->getValue() );
	}

	public function test_customImageTag_should_stand_in_for_the_template_it_narrows() {
		$this->assert_stands_in_for(
			$this->template_factory->customImageTag( new CustomImageTag() ),
			new CustomImageTag(),
			[ 'parameters' => [ 'customImageSrc' => home_url( '/pixel.gif' ) ] ]
		);
	}

	public function test_matomoConfigurationVariable_should_refuse_a_matomo_url_on_another_host() {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'matomoUrl' )->setValue( 'https://evil.example/matomo/' );
	}

	public function test_matomoConfigurationVariable_should_accept_a_matomo_url_on_this_site() {
		$site = wp_parse_url( home_url() );
		$url  = '//' . $site['host'] . ( isset( $site['port'] ) ? ':' . $site['port'] : '' ) . $this->matomo_app_path();

		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );
		$parameter->setValue( $url );

		$this->assertSame( $url, $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_accept_the_matomo_app_path_without_a_trailing_slash() {
		$url = untrailingslashit( home_url( $this->matomo_app_path() ) );
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );
		$parameter->setValue( $url );

		$this->assertSame( $url, $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_refuse_a_same_site_non_matomo_path() {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'matomoUrl' )->setValue( home_url( '/wp-json/wp/v2/users/2/' ) );
	}

	public function test_matomoConfigurationVariable_should_refuse_the_matomo_path_on_another_port() {
		$site = wp_parse_url( home_url() );
		$port = isset( $site['port'] ) ? (int) $site['port'] + 1 : ( 'https' === $site['scheme'] ? 444 : 81 );
		$url  = $site['scheme'] . '://' . $site['host'] . ':' . $port . $this->matomo_app_path();
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'matomoUrl' )->setValue( $url );
	}

	public function test_matomoConfigurationVariable_should_accept_its_own_default_matomo_url() {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );
		$parameter->setValue( $parameter->getDefaultValue() );

		$this->assertSame( $parameter->getDefaultValue(), $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_drop_a_query_string_from_the_matomo_url() {
		$app_path = $this->matomo_app_path();
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );
		$parameter->setValue( home_url( $app_path . '?x=' ) );

		$this->assertSame( home_url( $app_path ), $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_drop_a_fragment_from_the_matomo_url() {
		$app_path = $this->matomo_app_path();
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );
		$parameter->setValue( home_url( $app_path . '#' ) );

		$this->assertSame( home_url( $app_path ), $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_propose_a_matomo_url_default_this_site_answers_to() {
		// set OPTION_PIWIK_URL to a completely different domain
		$this->set_matomo_url_option( 'https://cdn.example/wp-content/plugins/matomo/app/' );

		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );

		$this->assertSame( $this->matomo_app_path(), $parameter->getDefaultValue() );

		$parameter->setValue( $parameter->getDefaultValue() );

		$this->assertSame( $this->matomo_app_path(), $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_leave_a_matomo_url_default_on_this_site_as_matomo_proposed_it() {
		$this->set_matomo_url_option( home_url( $this->matomo_app_path() ) );

		$matomo_default = $this->find_parameter( new MatomoConfigurationVariable(), 'matomoUrl' )->getDefaultValue();

		$this->assertNotEmpty( $matomo_default, 'Matomo must propose something to leave alone' );
		$this->assertSame(
			$matomo_default,
			$this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' )->getDefaultValue()
		);
	}

	public function unsalvageable_matomo_url_provider() {
		return [
			// wp_make_link_relative() answers '' for this one, and an empty default fails NotEmpty()
			'a url that is nothing but a host'    => [ '//cdn.example' ],
			// and it leaves this one exactly as it found it, since its scheme is not followed by "//"
			'a scheme that is not followed by //' => [ 'https:cdn.example/wp-content/plugins/matomo/app/' ],
		];
	}

	/**
	 * @dataProvider unsalvageable_matomo_url_provider
	 */
	public function test_matomoConfigurationVariable_should_propose_a_savable_matomo_url_default_however_unusable_the_stored_one_is( $stored ) {
		$this->set_matomo_url_option( $stored );

		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'matomoUrl' );

		// whatever is proposed has to survive the field's own validators, or every save of the
		// whole variable failszz
		$parameter->setValue( $parameter->getDefaultValue() );

		$this->assertSame(
			$this->matomo_app_path(),
			$parameter->getValue()
		);
	}

	public function test_matomoConfigurationVariable_should_keep_matomos_own_validators_on_the_matomo_url() {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		// NotEmpty() is Matomo's, and appending ours must not replace the field config
		$this->find_parameter( $variable, 'matomoUrl' )->setValue( '' );
	}

	public function custom_endpoint_provider() {
		return [
			[ 'jsEndpointCustom' ],
			[ 'trackingEndpointCustom' ],
		];
	}

	public function test_javaScriptVariable_should_refuse_arbitrary_window_property_access() {
		$variable = $this->template_factory->javaScriptVariable( new JavaScriptVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'variableName' )->setValue( 'wpApiSettings.nonce' );
	}

	public function test_matomoConfigurationVariable_should_allow_empty_tracking_url_append() {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'appendToTrackingUrl' );
		$parameter->setValue( '' );

		$this->assertSame( '', $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_allow_non_authentication_tracking_parameters() {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'appendToTrackingUrl' );
		$parameter->setValue( 'campaign=spring&source=newsletter' );

		$this->assertSame( 'campaign=spring&source=newsletter', $parameter->getValue() );
	}

	public function unsafe_tracking_url_append_provider() {
		return [
			'nonce parameter' => [ '_wpnonce=123' ],
			'encoded nonce parameter' => [ '%5Fwpnonce=123' ],
			'authorization parameter' => [ 'Authorization=Bearer%20token' ],
			'variable interpolation' => [ 'campaign={{Nonce}}' ],
		];
	}

	/**
	 * @dataProvider unsafe_tracking_url_append_provider
	 */
	public function test_matomoConfigurationVariable_should_refuse_authentication_data_in_tracking_url_append( $value ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), 'appendToTrackingUrl' );

		$this->expectException( ValidatorException::class );

		$parameter->setValue( $value );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_refuse_a_variable_reference_in_a_custom_endpoint( $name ) {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, $name )->setValue( '{{MyEndpoint}}' );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_refuse_an_empty_custom_endpoint( $name ) {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, $name )->setValue( '' );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_refuse_a_custom_endpoint_that_walks_out_of_the_matomo_url( $name ) {
		// the endpoint is concatenated onto the Matomo URL, and the browser resolves "../" away
		// before it asks for anything, so a pinned Matomo URL is worth nothing without this
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, $name )->setValue( '../../wp-content/uploads/payload.txt' );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_refuse_a_custom_endpoint_that_could_address_an_upload( $name ) {
		$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, $name )->setValue( 'payload.txt' );
	}

	public function test_matomoConfigurationVariable_should_allow_custom_urls_when_url_restrictions_are_disabled() {
		$disable_restrictions = static function () {
			return false;
		};
		add_filter( 'matomo_tag_manager_restrict_urls', $disable_restrictions );

		try {
			$variable = $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() );
			$matomo_url = $this->find_parameter( $variable, 'matomoUrl' );
			$matomo_url->setValue( 'https://tracker.example/custom-matomo/' );
			$this->assertSame( 'https://tracker.example/custom-matomo/', $matomo_url->getValue() );

			$endpoint = $this->find_parameter( $variable, 'trackingEndpointCustom' );
			$endpoint->setValue( 'custom-tracker.php' );
			$this->assertSame( 'custom-tracker.php', $endpoint->getValue() );
			$endpoint->setValue( '../../wp-content/uploads/payload.txt' );
			$this->assertSame( '../../wp-content/uploads/payload.txt', $endpoint->getValue() );

			$append = $this->find_parameter( $variable, 'appendToTrackingUrl' );
			$append->setValue( '_wpnonce=123&campaign={{Campaign}}' );
			$this->assertSame( '_wpnonce=123&campaign={{Campaign}}', $append->getValue() );

			$this->assertArrayNotHasKey( JavaScriptVariable::class, $this->template_factory->getVariableReplacements() );
			$variable_name = $this->find_parameter( new JavaScriptVariable(), 'variableName' );
			$variable_name->setValue( 'wpApiSettings.nonce' );
			$this->assertSame( 'wpApiSettings.nonce', $variable_name->getValue() );
		} finally {
			remove_filter( 'matomo_tag_manager_restrict_urls', $disable_restrictions );
		}
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_accept_a_directory_for_a_custom_endpoint( $name ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( 'js/' );

		$this->assertSame( 'js/', $parameter->getValue() );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_accept_its_own_default_for_a_custom_endpoint( $name ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( $parameter->getDefaultValue() );

		$this->assertSame( $parameter->getDefaultValue(), $parameter->getValue() );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_accept_a_plain_value_for_a_custom_endpoint( $name ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( 'custom.js' );

		$this->assertSame( 'custom.js', $parameter->getValue() );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_drop_a_query_string_from_a_custom_endpoint( $name ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( 'matomo.js?cache=1' );

		$this->assertSame( 'matomo.js', $parameter->getValue() );
	}

	/**
	 * @dataProvider custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_drop_a_fragment_from_a_custom_endpoint( $name ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( 'matomo.js#x' );

		$this->assertSame( 'matomo.js', $parameter->getValue() );
	}

	public function rooted_custom_endpoint_provider() {
		$endpoints = [
			'a leading slash' => [ '/custom.js', 'custom.js' ],
			'two of them'     => [ '//matomo.php', 'matomo.php' ],
		];

		$cases = [];
		foreach ( $this->custom_endpoint_provider() as $name ) {
			foreach ( $endpoints as $label => $endpoint ) {
				$cases[ $name[0] . ', ' . $label ] = [ $name[0], $endpoint[0], $endpoint[1] ];
			}
		}

		return $cases;
	}

	/**
	 * @dataProvider rooted_custom_endpoint_provider
	 */
	public function test_matomoConfigurationVariable_should_drop_leading_slashes_from_a_custom_endpoint( $name, $stored, $expected ) {
		$parameter = $this->find_parameter( $this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ), $name );
		$parameter->setValue( $stored );

		$this->assertSame( $expected, $parameter->getValue() );
	}

	public function test_matomoConfigurationVariable_should_stand_in_for_the_template_it_narrows() {
		$this->assert_stands_in_for(
			$this->template_factory->matomoConfigurationVariable( new MatomoConfigurationVariable() ),
			new MatomoConfigurationVariable(),
			[]
		);
	}

	public function test_customJsFunctionVariable_should_refuse_any_js_function() {
		$variable = $this->template_factory->customJsFunctionVariable( new CustomJsFunctionVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'jsFunction' )->setValue( 'function () { return document.cookie; }' );
	}

	public function test_customJsFunctionVariable_should_refuse_the_default_js_function_too() {
		$parameter = $this->find_parameter( $this->template_factory->customJsFunctionVariable( new CustomJsFunctionVariable() ), 'jsFunction' );

		$this->expectException( ValidatorException::class );

		$parameter->setValue( $parameter->getDefaultValue() );
	}

	public function test_customJsFunctionVariable_should_stand_in_for_the_template_it_narrows() {
		$this->assert_stands_in_for(
			$this->template_factory->customJsFunctionVariable( new CustomJsFunctionVariable() ),
			new CustomJsFunctionVariable(),
			[ 'parameters' => [ 'jsFunction' => 'function () { return "x"; }' ] ]
		);
	}

	public function test_customRequestProcessingVariable_should_refuse_any_js_function() {
		$variable = $this->template_factory->customRequestProcessingVariable( new CustomRequestProcessingVariable() );

		$this->expectException( ValidatorException::class );

		$this->find_parameter( $variable, 'jsFunction' )->setValue( 'function (request) { return request; }' );
	}

	public function test_customRequestProcessingVariable_should_refuse_the_default_js_function_too() {
		$parameter = $this->find_parameter( $this->template_factory->customRequestProcessingVariable( new CustomRequestProcessingVariable() ), 'jsFunction' );

		$this->expectException( ValidatorException::class );

		$parameter->setValue( $parameter->getDefaultValue() );
	}

	public function test_customRequestProcessingVariable_should_stand_in_for_the_template_it_narrows() {
		$this->assert_stands_in_for(
			$this->template_factory->customRequestProcessingVariable( new CustomRequestProcessingVariable() ),
			new CustomRequestProcessingVariable(),
			[ 'parameters' => [ 'jsFunction' => 'function (request) { return request; }' ] ]
		);
	}

	/**
	 * Asserts that a secured template behaves exactly as the template it replaces/wraps
	 * (apart from the extr validations added).
	 *
	 * @param \Piwik\Plugins\TagManager\Template\BaseTemplate $secured
	 * @param \Piwik\Plugins\TagManager\Template\BaseTemplate $wrapped
	 * @param array                                           $entity
	 */
	private function assert_stands_in_for( $secured, $wrapped, $entity ) {
		$this->assertInstanceOf( get_class( $wrapped ), $secured );

		$this->assertSame( $wrapped->getId(), $secured->getId() );
		$this->assertSame( $wrapped->getName(), $secured->getName() );
		$this->assertSame( $wrapped->getDescription(), $secured->getDescription() );
		$this->assertSame( $wrapped->getHelp(), $secured->getHelp() );

		// a name that fell back to the ID would mean the translation key was not found
		$this->assertNotSame( $secured->getId(), $secured->getName() );

		$this->assertSame(
			$this->parameter_names( $wrapped->getParameters() ),
			$this->parameter_names( $secured->getParameters() )
		);

		// loadTemplate() asks the DI container for TagManagerJSMinificationEnabled, which is only
		// registered when Tag Manager is activated
		if ( Manager::getInstance()->isPluginActivated( 'TagManager' ) ) {
			$expected = $wrapped->loadTemplate( WebContext::ID, $entity );

			$this->assertNotEmpty( $expected, 'the template Matomo ships must not be empty' );
			$this->assertSame( $expected, $secured->loadTemplate( WebContext::ID, $entity ) );
		}
	}

	/**
	 * @param string $url what SettingsPiwik::getPiwikUrl() should report from here on
	 */
	private function set_matomo_url_option( $url ) {
		if ( ! isset( $this->previous_matomo_url_option ) ) {
			$this->previous_matomo_url_option = Option::get( SettingsPiwik::OPTION_PIWIK_URL );
		}

		Option::set( SettingsPiwik::OPTION_PIWIK_URL, $url );
	}

	/**
	 * @return string the root-relative path to this plugin's Matomo application
	 */
	private function matomo_app_path() {
		return wp_make_link_relative( rtrim( plugins_url( 'app', MATOMO_ANALYTICS_FILE ), '/' ) . '/' );
	}

	/**
	 * @param \Piwik\Plugins\TagManager\Template\BaseTemplate $template
	 * @param string                                          $name
	 * @return \Piwik\Settings\Setting
	 */
	private function find_parameter( $template, $name ) {
		foreach ( $template->getParameters() as $parameter ) {
			if ( $parameter->getName() === $name ) {
				return $parameter;
			}
		}

		$this->fail( sprintf( 'the %s parameter is no longer defined', $name ) );
	}

	private function parameter_names( $parameters ) {
		$names = [];
		foreach ( $parameters as $parameter ) {
			$names[] = $parameter->getName();
		}

		return $names;
	}
}
