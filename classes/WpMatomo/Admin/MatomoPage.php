<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

class MatomoPage {

	/**
	 * @var MatomoPageContent
	 */
	private $content;

	public function __construct( MatomoPageContent $content ) {
		$this->content = $content;
	}

	public function show() {
		$title = $this->content->get_title();
		?>
		<div class="wrap">
		<div id="icon-plugins" class="icon32"></div>
		<?php if ( ! empty( $title ) ) { ?>
			<h1><?php matomo_header_icon(); ?><?php echo esc_html( $title ); ?></h1>
			<?php
		}

		do_action( 'matomo_page_content_before' );

		$this->content->show();

		do_action( 'matomo_page_content_after' );
		?>
		</div>
		<?php
	}

	public function get_content() {
		return $this->content;
	}
}
