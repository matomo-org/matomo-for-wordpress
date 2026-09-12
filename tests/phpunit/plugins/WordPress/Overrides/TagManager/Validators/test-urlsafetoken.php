<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\UrlSafeToken;
use Piwik\Validators\Exception as ValidatorException;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class UrlSafeTokenTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var UrlSafeToken
	 */
	private $validator;

	public function setUp(): void {
		parent::setUp();

		$this->validator = new UrlSafeToken();
	}

	public function accepted_tokens_provider() {
		return [
			'a livezilla id'       => [ 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' ],
			'digits'               => [ '0123456789' ],
			'the unreserved marks' => [ 'a.b-c_d~e' ],
			'an empty value'       => [ '' ],
		];
	}

	/**
	 * @dataProvider accepted_tokens_provider
	 */
	public function test_validate_should_accept_a_token_that_cannot_change_what_a_url_addresses( $token ) {
		$this->validator->validate( $token );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function rejected_tokens_provider() {
		return [
			// the template puts this after "?id=", so these add parameters of the caller's choosing
			'another query parameter'    => [ 'aaaa&callback=alert' ],
			'a value for a parameter'    => [ 'aaaa=bbbb' ],
			// and these leave the query, or the path, altogether
			'a fragment'                 => [ 'aaaa#x' ],
			'a further question mark'    => [ 'aaaa?x' ],
			'a path separator'           => [ 'aaaa/bbbb' ],
			'a backslash'                => [ 'aaaa\\bbbb' ],
			'a percent encoded sequence' => [ 'aaaa%26callback%3Dalert' ],
			'a variable reference'       => [ '{{MyConstant}}' ],
			'a space'                    => [ 'aaaa bbbb' ],
		];
	}

	/**
	 * @dataProvider rejected_tokens_provider
	 */
	public function test_validate_should_reject_a_token_that_carries_url_syntax( $token ) {
		$this->expectException( ValidatorException::class );

		$this->validator->validate( $token );
	}

	public function test_validate_should_ignore_a_bare_value_and_leave_it_to_not_empty() {
		$this->validator->validate( null );
		$this->validator->validate( false );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function test_validate_should_reject_a_value_that_is_not_a_string() {
		$this->expectException( ValidatorException::class );

		$this->validator->validate( [ 'aaaa' ] );
	}
}
