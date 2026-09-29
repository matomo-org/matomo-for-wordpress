<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * https://github.com/braekling/matomo
 *
 */

use Piwik\Piwik;
use WpMatomo\Admin\ExclusionSettings;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $was_updated */
/** @var bool $exclude_visits_cookie */
/** @var string $current_ip */
/** @var string $excluded_ips */
/** @var string $excluded_user_agents */
/** @var string $excluded_query_params */
/** @var bool|string|int $keep_url_fragments */
/** @var Settings $settings */
/** @var string[] $settings_errors */
/** @var bool $is_network_wide_screen */
/** @var bool $can_edit_filter */
/** @var bool $can_edit_exclusions */
/** @var string $tracking_filter_key */
/** @var array<string, bool> $tracking_filter */
/** @var array<string, bool> $network_tracking_filter */
/** @var WP_Roles $wp_roles */
?>

<?php
if ( $was_updated ) {
	include 'update_notice_clear_cache.php';
}
if ( count( $settings_errors ) ) {
	include 'settings_errors.php';
}

$matomo_filter_disabled     = $can_edit_filter ? '' : ' disabled="disabled"';
$matomo_exclusions_disabled = $can_edit_exclusions ? '' : ' disabled="disabled"';

// tracking filter network-wide excluded roles. in the blog specific settings, these roles
// cannot be removed from the filter.
$matomo_network_excluded_roles = [];
foreach ( $network_tracking_filter as $matomo_role_name => $matomo_enabled ) {
	if ( $matomo_enabled && isset( $wp_roles->role_names[ $matomo_role_name ] ) ) {
		$matomo_network_excluded_roles[] = $wp_roles->role_names[ $matomo_role_name ];
	}
}
?>
<?php if ( $is_network_wide_screen ) { ?>
	<h2><?php esc_html_e( 'Exclusion settings', 'matomo' ); ?></h2>
	<p>
		<?php esc_html_e( 'The tracking filter below applies to every blog on this network. A blog can exclude further roles of its own, but cannot track a role that is excluded here.', 'matomo' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'The remaining exclusion settings have to be configured on a per blog basis. Should you wish to change any of them, please go to the Matomo exclusion settings within each blog. We are hoping to improve this in the future.', 'matomo' ); ?>
	</p>
<?php } ?>

	<form method="post">
		<?php wp_nonce_field( ExclusionSettings::NONCE_NAME ); ?>

		<?php if ( ! $is_network_wide_screen ) { ?>
			<p><?php esc_html_e( 'Configure exclusions.', 'matomo' ); ?></p>
		<?php } ?>
		<table class="matomo-tracking-form widefat">
			<tbody>

			<tr>
				<th width="20%" scope="row"><label><?php esc_html_e( 'Tracking filter', 'matomo' ); ?></label>:
				</th>
				<td>
					<?php
					foreach ( $wp_roles->role_names as $matomo_key => $matomo_name ) {
						echo '<input type="checkbox" '
							. ( isset( $tracking_filter[ $matomo_key ] ) && $tracking_filter[ $matomo_key ] ? 'checked="checked" ' : '' )
							. 'value="1" name="'
							. esc_attr( ExclusionSettings::FORM_NAME ) . '[' . esc_attr( $tracking_filter_key ) . '][' . esc_attr( $matomo_key ) . ']"'
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							. $matomo_filter_disabled
							. ' /> ' . esc_html( $matomo_name ) . ' &nbsp; <br />';
					}
					?>
				</td>
				<td width="50%">
					<?php echo sprintf( esc_html__( 'Choose users by user role you do %1$snot%2$s want to track.', 'matomo' ), '<strong>', '</strong>' ); ?>
					<?php if ( $is_network_wide_screen ) { ?>
						<br/>
						<p>
							<strong><?php esc_html_e( 'This setting applies to every blog on this network. Each blog can exclude further roles of its own on its own Matomo exclusion settings.', 'matomo' ); ?></strong>
						</p>
					<?php } elseif ( $settings->is_network_enabled() ) { ?>
						<br/><p><?php esc_html_e( 'This setting applies to this blog only.', 'matomo' ); ?>
							<?php if ( ! empty( $matomo_network_excluded_roles ) ) { ?>
								<?php esc_html_e( 'Users with these roles are already excluded on every blog of this network', 'matomo' ); ?>:
								<strong><?php echo esc_html( implode( ', ', $matomo_network_excluded_roles ) ); ?></strong>.
							<?php } ?>
						</p>
					<?php } ?>
				</td>
			</tr>
			<?php if ( ! $is_network_wide_screen ) { ?>
			<tr>
				<th width="20%" scope="row">
					<label><?php echo esc_html( Piwik::translate( 'SitesManager_GlobalListExcludedIps' ) ); ?></label>:
				</th>
				<td width="30%">
					<?php
						echo sprintf(
							'<textarea cols="40" rows="4" id="%1$s" name="' . esc_attr( ExclusionSettings::FORM_NAME ) . '[%1$s]"%3$s>%2$s</textarea>',
							'excluded_ips',
							esc_html( $excluded_ips ),
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$matomo_exclusions_disabled
						);
					?>
				</td>
				<td width="50%">
					<?php
					echo esc_html(
						Piwik::translate(
							'SitesManager_HelpExcludedIpAddresses',
							[
								'1.2.3.4/24',
								'1.2.3.*',
								'1.2.*.*',
							]
						)
					)
					?>
					<br/>
					<?php echo esc_html( Piwik::translate( 'SitesManager_YourCurrentIpAddressIs', esc_html( $current_ip ) ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php echo esc_html( Piwik::translate( 'SitesManager_GlobalListExcludedQueryParameters' ) ); ?></label>:
				</th>
				<td>
					<?php
						echo sprintf(
							'<textarea cols="40" rows="4" id="%1$s" name="' . esc_attr( ExclusionSettings::FORM_NAME ) . '[%1$s]"%3$s>%2$s</textarea>',
							'excluded_query_parameters',
							esc_html( $excluded_query_params ),
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$matomo_exclusions_disabled
						);
					?>
				</td>
				<td>
					<?php echo esc_html( Piwik::translate( 'SitesManager_ListOfQueryParametersToExclude', '/^sess.*|.*[dD]ate$/' ) ); ?>
					<?php echo esc_html( Piwik::translate( 'SitesManager_PiwikWillAutomaticallyExcludeCommonSessionParameters', 'phpsessid, sessionid, ...' ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php echo esc_html( Piwik::translate( 'SitesManager_GlobalListExcludedUserAgents' ) ); ?></label>:
				</th>
				<td>
					<?php
						echo sprintf(
							'<textarea cols="40" rows="4" id="%1$s" name="' . esc_attr( ExclusionSettings::FORM_NAME ) . '[%1$s]"%3$s>%2$s</textarea>',
							'excluded_user_agents',
							esc_html( $excluded_user_agents ),
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$matomo_exclusions_disabled
						);
					?>
				</td>
				<td>

					<?php echo esc_html( Piwik::translate( 'SitesManager_GlobalExcludedUserAgentHelp1' ) ); ?>
					<br/>
					<?php echo esc_html( Piwik::translate( 'SitesManager_GlobalListExcludedUserAgents_Desc' ) ); ?>
					<?php echo esc_html( Piwik::translate( 'SitesManager_GlobalExcludedUserAgentHelp2' ) ); ?>
					<?php echo esc_html( Piwik::translate( 'SitesManager_GlobalExcludedUserAgentHelp3', '/bot|spider|crawl|scanner/i' ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php echo esc_html( Piwik::translate( 'SitesManager_KeepURLFragmentsLong' ) ); ?></label>:
				</th>
				<td>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo sprintf(
						'<input type="checkbox" value="1" %2$s%3$s name="' . esc_attr( ExclusionSettings::FORM_NAME ) . '[%1$s]">',
						'keep_url_fragments',
						$keep_url_fragments ? ' checked="checked"' : '',
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$matomo_exclusions_disabled
					);
					?>
				</td>
				<td>

					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo Piwik::translate(
						'SitesManager_KeepURLFragmentsHelp',
						[
							'<em>#</em>',
							'<em>example.org/index.html#first_section</em>',
							'<em>example.org/index.html</em>',
						]
					)
					?>
					<br/>
					<?php echo esc_html( Piwik::translate( 'SitesManager_KeepURLFragmentsHelp2' ) ); ?>

				</td>
			</tr>
			<?php } ?>
			<?php if ( $can_edit_filter || $can_edit_exclusions ) { ?>
			<tr>
				<td colspan="3">
					<p class="submit"><input name="Submit" type="submit" class="button-primary"
											value="<?php echo esc_attr__( 'Save Changes', 'matomo' ); ?>"/></p>
				</td>
			</tr>
			<?php } ?>

			</tbody>
		</table>
	</form>
