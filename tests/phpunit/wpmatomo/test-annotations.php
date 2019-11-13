<?php
/**
 * @package matomo
 */

use Piwik\Access;
use Piwik\Plugins\Annotations\API;
use WpMatomo\Annotations;
use WpMatomo\Bootstrap;
use WpMatomo\Settings;
use WpMatomo\Site;

class AnnotationsTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Annotations
	 */
	private $annotations;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings = new Settings();
		// enable tracking
		$this->settings->apply_changes(
			array(
				'track_mode'           => 'manually',
				'add_post_annotations' => array(
					'guides' => true,
					'faq'    => true,
				),
			)
		);

		$this->annotations = new Annotations( $this->settings );
		$this->annotations->register_hooks();
	}

	private function get_all_annotations() {
		Bootstrap::do_bootstrap();
		$all    = null;
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		Access::doAsSuperUser(
			function () use ( &$all, $idsite ) {
					$all = API::getInstance()->getAll( $idsite );
			}
		);
		if ( isset( $all[ $idsite ] ) ) {
			return $all[ $idsite ];
		}

		return $all;
	}

	public function test_add_annotation_not_sync_when_post_type_not_allowed() {
		$post_id = self::factory()->post->create_and_get( array( 'post_title' => 'hello-world' ) );
		wp_publish_post( $post_id );
		$this->assertSame( array(), $this->get_all_annotations() );
	}

	public function test_add_annotation_when_post_type_allowed() {
		$this->settings->apply_changes(
			array(
				'add_post_annotations' => array(
					'guides' => true,
					'faq'    => true,
					'post'   => true,
				),
			)
		);

		$post_id = self::factory()->post->create_and_get( array( 'post_title' => 'hello-world' ) );
		wp_publish_post( $post_id );

		$this->assertSame(
			array(
				array(
					'date'            => gmdate( 'Y-m-d' ),
					'note'            => 'Published: hello-world - URL: http://example.org/?p=5',
					'starred'         => 0,
					'user'            => 'super user was set',
					'idNote'          => 0,
					'canEditOrDelete' => true,
				),
			),
			$this->get_all_annotations()
		);
	}

}
