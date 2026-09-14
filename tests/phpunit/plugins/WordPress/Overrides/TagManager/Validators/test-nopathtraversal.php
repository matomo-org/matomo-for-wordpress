<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\NoPathTraversal;
use Piwik\Validators\Exception as ValidatorException;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class NoPathTraversalTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var NoPathTraversal
	 */
	private $validator;

	public function setUp(): void {
		parent::setUp();

		$this->validator = new NoPathTraversal();
	}

	public function accepted_paths_provider() {
		return [
			'the default custom js endpoint'       => [ 'custom.js' ],
			'the default custom tracking endpoint' => [ 'custom.php' ],
			'a path below the matomo url'          => [ 'js/tracker.php' ],
			'a query string'                       => [ 'matomo.php?idsite=1' ],
			'a single dot segment'                 => [ './matomo.js' ],
			'a dot in a file name'                 => [ 'matomo.min.js' ],
			// a browser does not decode this a second time, so neither do we
			'a doubly encoded dot dot'             => [ '%252e%252e/payload.txt' ],
			'an empty value'                       => [ '' ],
		];
	}

	/**
	 * @dataProvider accepted_paths_provider
	 */
	public function test_validate_should_accept_a_path_that_stays_below_what_it_is_appended_to( $path ) {
		$this->validator->validate( $path );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function rejected_paths_provider() {
		return [
			'a dot dot segment'             => [ '../payload.txt' ],
			'a dot dot further along'       => [ 'js/../../wp-content/uploads/payload.txt' ],
			// "%2e%2e" is a dot dot segment to a URL parser just as ".." is
			'an encoded dot dot'            => [ '%2e%2e/payload.txt' ],
			'a half encoded dot dot'        => [ '.%2e/payload.txt' ],
			'an upper case encoded dot dot' => [ '%2E%2E/payload.txt' ],
			// browsers normalise backslashes to forward slashes before resolving the path
			'a dot dot with backslashes'    => [ '..\\wp-content\\uploads\\payload.txt' ],
		];
	}

	/**
	 * @dataProvider rejected_paths_provider
	 */
	public function test_validate_should_reject_a_path_that_walks_back_out_of_what_it_is_appended_to( $path ) {
		$this->expectException( ValidatorException::class );

		$this->validator->validate( $path );
	}

	public function test_validate_should_ignore_a_bare_value_and_leave_it_to_not_empty() {
		$this->validator->validate( null );
		$this->validator->validate( false );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function test_validate_should_reject_a_value_that_is_not_a_string() {
		$this->expectException( ValidatorException::class );

		$this->validator->validate( [ '../payload.txt' ] );
	}
}
