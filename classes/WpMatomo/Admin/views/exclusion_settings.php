<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * @package WP_Matomo
 * https://github.com/braekling/matomo
 *
 */

use Piwik\Piwik;
use WpMatomo\Admin\ExclusionSettings;

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
/** @var \WpMatomo\Settings $settings */

?>

<?php if ( $was_updated ) {
	include 'update_notice_clear_cache.php';
} ?>
<form method="post">
	<?php wp_nonce_field( ExclusionSettings::NONCE_NAME ); ?>

    <p><?php _e('Configure exclusions.', 'matomo') ?></p>
    <table class="matomo-tracking-form widefat">
        <tbody>

        <tr>
            <th width="20%" scope="row"><label
                        for="%2$s"><?php echo __( 'Tracking filter', 'matomo' ) ?></label>:
            </th>
            <td >
                <?php
                $trackingCaps = \WpMatomo\Settings::OPTION_KEY_STEALTH;
                $filter = $settings->get_global_option( $trackingCaps );
                foreach ( $wp_roles->role_names as $key => $name ) {
                    echo '<input type="checkbox" ' . ( isset ( $filter [ $key ] ) && $filter [ $key ] ? 'checked="checked" ' : '' ) . 'value="1" name="' . ExclusionSettings::FORM_NAME . '[' . $trackingCaps . '][' . $key . ']" /> ' . $name . ' &nbsp; <br />';
                }
            ?>
            </td>
            <td width="50%">
		        <?php echo sprintf(__( 'Choose users by user role you do %1$snot%2$s want to track.', 'matomo' ), '<strong>', '</strong>') ?>
            </td>
        </tr>
        <tr>
            <th width="20%" scope="row"><label
                        for="%2$s"><?php echo Piwik::translate( 'SitesManager_GlobalListExcludedIps' ) ?></label>:
            </th>
            <td width="30%">
				<?php echo sprintf( '<textarea cols="40" rows="4" id="%1$s" name="' . ExclusionSettings::FORM_NAME . '[%1$s]">%2$s</textarea>', 'excluded_ips', esc_html( $excluded_ips ) ); ?>
            </td>
            <td width="50%">
				<?php echo Piwik::translate( 'SitesManager_HelpExcludedIpAddresses', array(
					'1.2.3.4/24',
					'1.2.3.*',
					'1.2.*.*'
				) ) ?>
                <br/>
				<?php echo Piwik::translate( 'SitesManager_YourCurrentIpAddressIs', esc_html( $current_ip ) ) ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label
                        for="%2$s"><?php echo Piwik::translate( 'SitesManager_GlobalListExcludedQueryParameters' ) ?></label>:
            </th>
            <td>
				<?php echo sprintf( '<textarea cols="40" rows="4" id="%1$s" name="' . ExclusionSettings::FORM_NAME . '[%1$s]">%2$s</textarea>', 'excluded_query_parameters', esc_html( $excluded_query_params ) ); ?>
            </td>
            <td>
				<?php echo Piwik::translate( 'SitesManager_ListOfQueryParametersToExclude', '/^sess.*|.*[dD]ate$/' ) ?>
				<?php echo Piwik::translate( 'SitesManager_PiwikWillAutomaticallyExcludeCommonSessionParameters', 'phpsessid, sessionid, ...' ) ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label
                        for="%2$s"><?php echo Piwik::translate( 'SitesManager_GlobalListExcludedUserAgents' ) ?></label>:
            </th>
            <td>
				<?php echo sprintf( '<textarea cols="40" rows="4" id="%1$s" name="' . ExclusionSettings::FORM_NAME . '[%1$s]">%2$s</textarea>', 'excluded_user_agents', esc_html( $excluded_user_agents ) ); ?>
            </td>
            <td>

				<?php echo Piwik::translate( 'SitesManager_GlobalExcludedUserAgentHelp1' ) ?>
                <br/>
				<?php echo Piwik::translate( 'SitesManager_GlobalListExcludedUserAgents_Desc' ) ?>
				<?php echo Piwik::translate( 'SitesManager_GlobalExcludedUserAgentHelp2' ) ?>

            </td>
        </tr>
        <tr>
            <th scope="row"><label
                        for="%2$s"><?php echo Piwik::translate( 'SitesManager_KeepURLFragmentsLong' ) ?></label>:
            </th>
            <td>
				<?php echo sprintf( '<input type="checkbox" value="1" %2$s name="' . ExclusionSettings::FORM_NAME . '[%1$s]">', 'keep_url_fragments', $keep_url_fragments ? ' checked="checked"' : '' ); ?>
            </td>
            <td>

				<?php echo Piwik::translate( 'SitesManager_KeepURLFragmentsHelp', array(
					'<em>#</em>',
					'<em>example.org/index.html#first_section</em>',
					'<em>example.org/index.html</em>'
				) ) ?>
                <br/>
				<?php echo Piwik::translate( 'SitesManager_KeepURLFragmentsHelp2' ) ?>

            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p class="submit"><input name="Submit" type="submit" class="button-primary"
                                         value="<?php echo esc_attr__( 'Save Changes' ) ?>"/></p>
            </td>
        </tr>

        </tbody>
    </table>
</form>