<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\MarketplaceSetupWizardBody;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var MarketplaceSetupWizardBody $marketplace_setup_wizard_body
 */
?>
<div class="matomo-marketplace-wizard" data-current-step="0">
	<div class="matomo-marketplace-wizard-header">
		<div class="matomo-marketplace-wizard-logo">
			<img alt="Matomo Logo" src="<?php echo esc_attr( $matomo_logo_big ); ?>" />
		</div>
	</div>

	<?php $marketplace_setup_wizard_body->show(); ?>
</div>
