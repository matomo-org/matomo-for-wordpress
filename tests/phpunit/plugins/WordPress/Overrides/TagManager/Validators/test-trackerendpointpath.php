<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\TrackerEndpointPath;
use Piwik\Validators\Exception as ValidatorException;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
class TrackerEndpointPathTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var TrackerEndpointPath
	 */
	private $validator;

	public function setUp(): void {
		parent::setUp();

		$this->validator = new TrackerEndpointPath();
	}

	public function accepted_paths_provider() {
		return [
			'the default custom js endpoint'       => [ 'custom.js' ],
			'the default custom tracking endpoint' => [ 'custom.php' ],
			'a js file below the matomo url'       => [ 'js/tracker.js' ],
			'a php file below the matomo url'      => [ 'js/tracker.php' ],
			'an upper case extension'              => [ 'MATOMO.JS' ],
			'a dot in a file name'                 => [ 'matomo.min.js' ],
			// Matomo's own "js/" endpoint. a directory is not something an upload can become
			'a directory'                          => [ 'js/' ],
			'a query string on a php endpoint'     => [ 'matomo.php?idsite=1' ],
			'a fragment on a js endpoint'          => [ 'matomo.js#x' ],
			// nothing but a query leaves the Matomo URL addressing itself, which is a directory
			'a query string on its own'            => [ '?idsite=1' ],
			'an empty value'                       => [ '' ],
		];
	}

	/**
	 * @dataProvider accepted_paths_provider
	 */
	public function test_validate_should_accept_a_path_that_addresses_a_tracker_endpoint( $path ) {
		$this->validator->validate( $path );

		$this->assertTrue( true, 'no exception was thrown' );
	}

	public function rejected_paths_provider() {
		return [
			// the extension nobody needs a plugin's help to upload
			'an uploaded text file'                       => [ 'payload.txt' ],
			'a file with no extension at all'             => [ 'payload' ],
			'an image'                                    => [ 'payload.gif' ],
			// the query is not part of what the server looks up, so it cannot make a path acceptable
			'an extension hidden in the query'            => [ 'payload.txt?x=.js' ],
			'an extension hidden in the fragment'         => [ 'payload.txt#.js' ],
			// a browser does not decode "%3F", so this addresses no ".js" file either
			'an extension after an encoded question mark' => [ 'payload.txt%3Fx=.js' ],
			'an extension after an encoded null byte'     => [ 'payload.txt%00.js' ],
			'a space in the path'                         => [ 'my endpoint.js' ],
			'a backslash in the path'                     => [ 'js\\tracker.js' ],
			'a path parameter'                            => [ 'payload.txt;x.js' ],
			// an endpoint is appended to the Matomo URL, so it addresses no host of its own
			'an absolute url'                             => [ 'https://evil.example/payload.js' ],
		];
	}

	/**
	 * @dataProvider rejected_paths_provider
	 */
	public function test_validate_should_reject_a_path_that_could_address_an_uploaded_file( $path ) {
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

		$this->validator->validate( [ 'custom.js' ] );
	}
}
