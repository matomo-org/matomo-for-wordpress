<?php
/**
 * @package matomo
 */
class ScopedTwigTest extends \PHPUnit\Framework\TestCase {

	public function test_deprecated_template_renders_with_only_matomo_dependencies() {
		// A fresh PHP process must not rely on PHPUnit or another plugin defining the helper.
		$autoload = dirname( __DIR__, 3 ) . '/app/vendor/autoload.php';
		$code     = 'require ' . var_export( $autoload, true ) . ';'
			. '$loader = new \Matomo\Dependencies\Twig\Loader\ArrayLoader(['
			. '"test" => "{% deprecated \"Compatibility test\" %}rendered"'
			. ']);'
			. '$twig = new \Matomo\Dependencies\Twig\Environment($loader);'
			. 'echo $twig->render("test");';

		$output = [];
		$status = 0;
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		exec( escapeshellarg( PHP_BINARY ) . ' -d display_errors=1 -d error_reporting=8191 -r ' . escapeshellarg( $code ) . ' 2>&1', $output, $status );

		$this->assertSame( 0, $status, implode( "\n", $output ) );
		$this->assertSame( 'rendered', implode( "\n", $output ) );
	}
}
