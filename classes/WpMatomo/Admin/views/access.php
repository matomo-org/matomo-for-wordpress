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

<p>Manage which roles can view and manage your reporting data. </p>

<form method="post">
	<?php wp_nonce_field( AccessSettings::NONCE_NAME ); ?>

    <table class="matomo-form widefat">
        <thead>
        <tr>
            <th width="30%">WordPress Role</th>
            <th>Matomo Role</th>
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
    Learn about the differences between these Matomo roles:
    <a href="https://matomo.org/faq/general/faq_70/" target="_blank" rel="noopener">View</a>,
    <a href="https://matomo.org/faq/general/faq_26910/" target="_blank" rel="noopener">Write</a>,
    <a href="https://matomo.org/faq/general/faq_69/" target="_blank" rel="noopener">Admin</a>,
    <a href="https://matomo.org/faq/general/faq_35/" target="_blank" rel="noopener">Super User</a>
</p>

<h2><?php echo __( 'Roles', 'matomo' ) ?></h2>
<p>Want to give individual users access to Matomo? Simply create a user in your WordPress and assign of these roles
    to the user:</p>
<ul class="matomo-list">
	<?php foreach ( $roles->get_matomo_roles() as $roleConfig ) { ?>
        <li><?php echo esc_html( $roleConfig['name'] ) ?></li>
	<?php } ?>
</ul>

<h2><?php echo __( 'Capabilities', 'matomo' ) ?></h2>
<p>You can also install a WordPress plugin which lets you manage capabilities for each individual users. These are
    the supported capabilities:</p>
<ul class="matomo-list">
	<?php
	foreach ( $capabilites->get_all_capabilities_sorted_by_highest_permission() as $capName ) { ?>
        <li><?php echo esc_html( $capName ) ?></li>
	<?php } ?>
</ul>
