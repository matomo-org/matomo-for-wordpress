<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WpMatomo\Access;
use WpMatomo\Admin\AccessSettings;

/** @var Access $access */
/** @var \WpMatomo\Roles $roles */
/** @var \WpMatomo\Capabilities $capabilites */
?>

<p><?php _e('Manage which roles can view and manage your reporting data.', 'matomo') ?></p>

<form method="post">
	<?php wp_nonce_field( AccessSettings::NONCE_NAME ); ?>

    <table class="matomo-form widefat">
        <thead>
        <tr>
            <th width="30%"><?php _e('WordPress Role', 'matomo') ?></th>
            <th><?php _e('Matomo Role', 'matomo') ?></th>
        </tr>
        </thead>
        <tbody>
		<?php
		foreach ( $roles->get_available_roles_for_configuration() as $roleId => $name ) {

			echo "<tr><td>";
			echo esc_html($name) . "</td>";
			echo "<td><select name='" . AccessSettings::FORM_NAME . "[" . esc_attr( $roleId ) . "]'>";
			$value = $access->get_permission_for_role( $roleId );
			foreach ( Access::$MATOMO_PERMISSIONS as $permission => $displayName ) {
				echo "<option value='" . esc_attr( $permission ) . "' " . ( $value === $permission ? 'selected' : '' ) . ">" . esc_html( $displayName ) . "</option>";
			}
			echo "</td></tr>";
		}
		?>
        <tr>
            <td colspan="2"><input name="Submit" type="submit" class="button-primary"
                                   value="<?php echo esc_attr__( 'Save Changes', 'matomo' ) ?>"/></td>
        </tr>
        </tbody>
    </table>
</form>

<p>
	<?php _e('Learn about the differences between these Matomo roles:', 'matomo') ?>
    <a href="https://matomo.org/faq/general/faq_70/" target="_blank" rel="noopener"><?php _e('View', 'matomo') ?></a>,
    <a href="https://matomo.org/faq/general/faq_26910/" target="_blank" rel="noopener"><?php _e('Write', 'matomo') ?></a>,
    <a href="https://matomo.org/faq/general/faq_69/" target="_blank" rel="noopener"><?php _e('Admin', 'matomo') ?></a>,
    <a href="https://matomo.org/faq/general/faq_35/" target="_blank" rel="noopener"><?php _e('Super User', 'matomo') ?></a>
</p>

<h2><?php echo __( 'Roles', 'matomo' ) ?></h2>
<p><?php _e('Want to give individual users access to Matomo? Simply create a user in your WordPress and assign of these roles
    to the user:', 'matomo') ?></p>
<ul class="matomo-list">
	<?php foreach ( $roles->get_matomo_roles() as $roleConfig ) { ?>
        <li><?php echo esc_html( $roleConfig['name'] ) ?></li>
	<?php } ?>
</ul>

<h2><?php echo __( 'Capabilities', 'matomo' ) ?></h2>
<p><?php _e('You can also install a WordPress plugin which lets you manage capabilities for each individual users. These are
    the supported capabilities:', 'matomo') ?></p>
<ul class="matomo-list">
	<?php
	foreach ( $capabilites->get_all_capabilities_sorted_by_highest_permission() as $capName ) { ?>
        <li><?php echo esc_html( $capName ) ?></li>
	<?php } ?>
</ul>
