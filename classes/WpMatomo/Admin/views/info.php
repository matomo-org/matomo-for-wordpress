<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use \WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
    <div id="icon-plugins" class="icon32"></div>

	<?php include 'info_shared.php'; ?>
	<?php include 'info_help.php'; ?>

    <h2>Support the project</h2>
    <p>Matomo is a collaborative project brought to you by <a target="_blank" rel="noreferrer noopener"
                                                                  href="https://matomo.org/team/">Matomo team</a>
        members as well as many other contributors around the globe. If you're a fan of Matomo,
        <a target="_blank" rel="noreferrer noopener" href="https://matomo.org/get-involved/">here’s how you can participate!</a>
        <br/><br/>
        You can also help us by <a href="<?php echo Menu::get_matomo_action_url(Menu::REPORTING_GOTO_ADMIN) ?>">donating</a> or by <a href="https://plugins.matomo.org/premium" target="_blank" rel="noreferrer noopener">purchasing premium plugins</a> which fund the
        development of the free Matomo Analytics version.
    </p>

    <h2>High traffic websites</h2>
	<?php include 'info_high_traffic.php'; ?>

    <?php include 'info_bug_report.php' ?>

    <div class="matomo-footer">
        <ul>
            <li>
                <a target="_blank" rel="noreferrer noopener" href="https://matomo.org/newsletter/"><span
                            class="dashicons-before dashicons-email"></span></a>
                <a target="_blank" rel="noreferrer noopener" href="https://matomo.org/newsletter/">Newsletter</a>
            </li>
            <li>
                <a target="_blank" rel="noreferrer noopener" href="https://www.facebook.com/Matomo.org"><span
                            class="dashicons-before dashicons-facebook"></span></a>
                <a target="_blank" rel="noreferrer noopener" href="https://www.facebook.com/Matomo.org">Facebook</a>
            </li>
            <li>
                <a target="_blank" rel="noreferrer noopener" href="https://twitter.com/matomo_org"><span
                            class="dashicons-before dashicons-twitter"></span></a>
                <a target="_blank" rel="noreferrer noopener" href="https://twitter.com/matomo_org">Twitter</a>
            </li>
            <li>
                <a target="_blank" rel="noreferrer noopener" href="https://www.linkedin.com/groups/867857/">Linkedin</a>
            </li>
            <li>
                <a target="_blank" rel="noreferrer noopener" href="https://github.com/matomo-org/matomo">GitHub</a>
            </li>
        </ul>
        <ul>
            <li><a target="_blank" rel="noreferrer noopener" href="https://matomo.org/blog/">Blog</a></li>
            <li><a target="_blank" rel="noreferrer noopener" href="https://developer.matomo.org">Developers</a></li>
            <li><a target="_blank" rel="noreferrer noopener" href="https://plugins.matomo.org">Marketplace</a></li>
            <li><a target="_blank" rel="noreferrer noopener" href="https://matomo.org/thank-you-all/">Credits</a></li>
        </ul>
    </div>
</div>
