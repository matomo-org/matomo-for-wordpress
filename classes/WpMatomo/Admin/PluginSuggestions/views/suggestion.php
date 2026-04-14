<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestion;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/** @var Suggestion $matomo_suggestion_to_show */
?>

<style>
	.matomo-plugin-suggestion {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		align-items: stretch;
		border-radius: 4px;
		border: solid 2px #e2e2e2;
		border-left: solid 4px #218532;
		background-color: white;
		padding: 0;
		position: relative;
	}

	.matomo-plugin-suggestion > * {
		padding-left: 1em;
		padding-right: 1em;
	}

	.matomo-plugin-suggestion-content {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: stretch;
	}

	.matomo-plugin-suggestion-body {
		flex: 1;
	}

	.matomo-plugin-suggestion-text-container,
	.matomo-plugin-suggestion-text,
	.matomo-plugin-suggestion-cta {
		padding-top: 1em;
		padding-bottom: 1em;
	}

	.matomo-plugin-suggestion-footer {
		text-align: right;
		line-height: 2em;
		border-top: 1px solid #F0F0F0;
		background-color: #FAFAFA;
		color: #A0A0A0;
	}

	.matomo-plugin-suggestion-footer > span {
		font-size: 12px;
	}

	.matomo-plugin-suggestion-body {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: stretch;
	}

	.matomo-plugin-suggestion-text-container {
		flex: 6;
	}

	.matomo-plugin-suggestion-img {
		flex: 3;
	}

	.matomo-plugin-suggestion-cta {
		flex: 1;
	}

	.matomo-plugin-suggestion-header {
		display: flex;
		flex-direction: row;
		justify-content: flex-start;
		align-items: center;
	}

	.matomo-fire-icon {
		margin-right: 10px;
	}

	.matomo-trigger-desc-short {
		padding: 1px 10px 1px 10px;
		text-transform: uppercase;
		color: #218532;
		background-color: rgba(33, 133, 50, 0.15);
		font-weight: bold;
		letter-spacing: 1.5px;
		border-radius: 2px;
		border: solid 1px rgba(33, 133, 50, 0.2);
		margin-right: 10px;
	}

	.matomo-trigger-desc-long {
		flex: 1;
		color: #555;
	}

	.matomo-dismiss-suggestion {
		text-decoration: none;
	}

	.matomo-plugin-suggestion-cta {
		display: flex;
		flex-direction: row;
		justify-content: flex-end;
		align-items: flex-start;
	}

	.matomo-plugin-suggestion-cta a {
		text-decoration: none;
	}

	.matomo-plugin-suggestion-cta .button-primary {
		background-color: #218532;
		border-color: #218532;
	}

	.matomo-plugin-suggestion-cta .button-primary:hover,
	.matomo-plugin-suggestion-cta .button-primary:focus {
		background-color: #175d23;
		border-color: #175d23;
	}

	.matomo-plugin-suggestion-cta {
		display: flex;
		justify-content: center;
		align-items: center;
	}

	.matomo-plugin-suggestion-text > div:first-child {
		font-weight: bold;
		font-size: 15px;
		margin-bottom: 4px;
	}

	.matomo-plugin-suggestion-text > div:last-child {
		color: #555;
	}

	.matomo-plugin-suggestion-img {
		background-repeat: no-repeat;
		background-size: contain;
		margin-top: 1em;
		margin-bottom: 1em;
		background-position: center;
	}

	@media (max-width: 640px) {
		.matomo-plugin-suggestion-img {
			display: none;
		}
	}
</style>
<div class="matomo-plugin-suggestion">
	<div class="matomo-plugin-suggestion-content">
		<div class="matomo-plugin-suggestion-text-container">
			<div class="matomo-plugin-suggestion-header">
				<span class="matomo-fire-icon">
					<svg width="14px" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 92.27 122.88" style="enable-background:new 0 0 92.27 122.88" xml:space="preserve"><style type="text/css">.st0{fill-rule:evenodd;clip-rule:evenodd;fill:#EC6F59;} .st1{fill-rule:evenodd;clip-rule:evenodd;fill:#FAD15C;}</style><g><path class="st0" d="M18.61,54.89C15.7,28.8,30.94,10.45,59.52,0C42.02,22.71,74.44,47.31,76.23,70.89 c4.19-7.15,6.57-16.69,7.04-29.45c21.43,33.62,3.66,88.57-43.5,80.67c-4.33-0.72-8.5-2.09-12.3-4.13C10.27,108.8,0,88.79,0,69.68 C0,57.5,5.21,46.63,11.95,37.99C12.85,46.45,14.77,52.76,18.61,54.89L18.61,54.89z"/><path class="st1" d="M33.87,92.58c-4.86-12.55-4.19-32.82,9.42-39.93c0.1,23.3,23.05,26.27,18.8,51.14 c3.92-4.44,5.9-11.54,6.25-17.15c6.22,14.24,1.34,25.63-7.53,31.43c-26.97,17.64-50.19-18.12-34.75-37.72 C26.53,84.73,31.89,91.49,33.87,92.58L33.87,92.58z"/></g></svg>
				</span>

				<div class="matomo-trigger-desc-short">
					<?php echo esc_html( $matomo_suggestion_to_show->get_trigger_desc_short() ); ?>
				</div>

				<div class="matomo-trigger-desc-long">
					<?php echo esc_html( $matomo_suggestion_to_show->get_trigger_desc_long() ); ?>
				</div>

				<a href="#" class="matomo-dismiss-suggestion notice-dismiss" data-suggestion="<?php echo esc_attr( $matomo_suggestion_to_show->get_short_id() ); ?>">
				</a>
			</div>

			<div class="matomo-plugin-suggestion-body">
				<div class="matomo-plugin-suggestion-text">
					<div>
						<?php echo esc_html( $matomo_suggestion_to_show->get_plugin_desc_long() ); ?>
					</div>

					<div>
						<?php echo esc_html( $matomo_suggestion_to_show->get_plugin_name() ); ?>
						&mdash;
						<?php echo esc_html( $matomo_suggestion_to_show->get_plugin_desc_short() ); ?>
					</div>
				</div>
			</div>
		</div>
		<div
			class="matomo-plugin-suggestion-img"
			<?php
			$matomo_suggestion_image = $matomo_suggestion_to_show->get_image_url();
			if ( ! empty( $matomo_suggestion_image ) ) {
				?>
			style="background-image: url(<?php echo esc_attr( $matomo_suggestion_image ); ?>)"
			<?php } ?>
		>

		</div>
		<div class="matomo-plugin-suggestion-cta">
			<div>
				<a rel="noreferrer noopener" target="_blank" href="<?php echo esc_attr( $matomo_suggestion_to_show->get_unlock_url() ); ?>">
					<button class="button-primary">
						<?php esc_html_e( 'Unlock', 'matomo' ); ?>
					</button>
				</a>
			</div>
		</div>
	</div>

	<div class="matomo-plugin-suggestion-footer">
		<span>
			<?php esc_html_e( 'Powered by Matomo Analytics', 'matomo' ); ?>
		</span>
	</div>
</div>
