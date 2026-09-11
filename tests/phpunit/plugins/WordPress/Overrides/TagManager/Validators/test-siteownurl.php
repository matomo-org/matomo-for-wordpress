<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\SiteOwnUrl;
use Piwik\Validators\Exception as ValidatorException;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class SiteOwnUrlTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var SiteOwnUrl
	 */
	private $validator;

	public function setUp(): void {
		parent::setUp();

		$this->validator = new SiteOwnUrl();
	}

	public function accepted_urls_provider() {
		// {host} is replaced with this site's own host, so the test cases don't depend on what the test
		// install is called.
		return [
			'the protocol relative default value' => [ '//{host}/wp-content/plugins/matomo/app/' ],
			'https on this site'                  => [ 'https://{host}/matomo/' ],
			'http on this site'                   => [ 'http://{host}/matomo/' ],
			'a differently cased host'            => [ 'https://{HOST}/matomo/' ],
			'a fully qualified host'              => [ 'https://{host}./matomo/' ],
			'another port on this site'           => [ 'https://{host}:8080/matomo/' ],
			'userinfo before this site'           => [ 'https://someone@{host}/matomo/' ],
			'an absolute path'                    => [ '/wp-content/plugins/matomo/app/' ],
			'a relative path'                     => [ 'wp-content/plugins/matomo/app/' ],
			'an empty value'                      => [ '' ],
		];
	}

	/**
	 * @dataProvider accepted_urls_provider
	 */
	public function test_validate_should_accept_a_url_on_this_site( $url ) {
		$this->validator->validate( $this->with_site_host( $url ) );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function rejected_urls_provider() {
		return [
			'another host'                    => [ 'https://evil.example/' ],
			'another host, protocol relative' => [ '//evil.example/' ],
			// parse_url() reports no host for this, but a browser reads it as https://evil.example/
			'a scheme without the slashes'    => [ 'https:evil.example' ],
			'this site as userinfo'           => [ 'https://{host}@evil.example/' ],
			// browsers normalise backslashes to forward slashes before parsing
			'backslashes'                     => [ '\\\\evil.example\\' ],
			'a mixed slash'                   => [ '/\\evil.example/' ],
			// browsers strip control characters and whitespace before parsing
			'an embedded newline'             => [ "htt\nps://evil.example/" ],
			'an embedded tab'                 => [ "//evil\t.example/" ],
			'a variable reference'            => [ '{{MyConstant}}' ],
			'a variable in an allowed host'   => [ '//{host}/{{MyPath}}/' ],
			'a javascript url'                => [ 'javascript:alert(1)' ],
			'a data url'                      => [ 'data:text/html,hi' ],
			'a scheme with no host at all'    => [ 'https://' ],
		];
	}

	/**
	 * @dataProvider rejected_urls_provider
	 */
	public function test_validate_should_reject_a_url_that_can_reach_another_origin( $url ) {
		$this->expectException( ValidatorException::class );

		$this->validator->validate( $this->with_site_host( $url ) );
	}

	public function test_validate_should_ignore_a_bare_value_and_leave_it_to_not_empty() {
		$this->validator->validate( null );
		$this->validator->validate( false );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function test_validate_should_name_the_rejected_host_and_the_allowed_one() {
		$expected_host = wp_parse_url( home_url(), PHP_URL_HOST );

		try {
			$this->validator->validate( 'https://evil.example/matomo/' );
			$this->fail( 'expected the validator to reject another host' );
		} catch ( ValidatorException $e ) {
			$this->assertStringContainsString( 'evil.example', $e->getMessage() );
			$this->assertStringContainsString( $expected_host, $e->getMessage() );
		}
	}

	private function with_site_host( $url ) {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return str_replace( [ '{host}', '{HOST}' ], [ $host, strtoupper( $host ) ], $url );
	}
}
