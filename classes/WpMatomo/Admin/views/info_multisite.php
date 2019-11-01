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

/** @var \WpMatomo\Settings $settings */
/** @var bool $canUserEdit */
?>

<div class="wrap">
    <div id="icon-plugins" class="icon32"></div>
    <h1><?php _e( 'Matomo Analytics in Multi Site mode', 'matomo' ); ?></h1>
    <p><?php _e( 'You are seeing this page as you are viewing the network admin. Matomo differentiates between two different multi site modes:', 'matomo' ); ?></p>
    <h2><?php _e( 'Matomo is network enabled', 'matomo' ); ?></h2>
    <p><?php _e( 'In this mode, the tracking and access settings are managed in the network admin in one place and apply to all blogs.', 'matomo' ); ?>
	    <?php _e( 'An administrator of a blog cannot view or change these settings.', 'matomo' ); ?>
        <br/><br/>
	    <?php _e( 'The license code for any possible premium features is applied across blogs and can only be managed by a super admin.', 'matomo' ); ?>
    </p>
    <h2><?php _e( 'Matomo is not network enabled', 'matomo' ); ?></h2>
    <p><?php _e( 'In this mode, the tracking and access settings are managed by each individual blog. They cannot be managed in one central place for all blogs. An administrator or any user with the "Matomo super user" role can change these settings.', 'matomo' ); ?>
        <br/>
        <br/>
	    <?php _e( 'The license code for any possible premium features is applied across blogs and can only be managed by a super admin.', 'matomo' ); ?>
    </p>
    <h2><?php _e( 'Managing many sites?', 'matomo' ); ?></h2>
    <p><?php echo sprintf(__('If you are managing quite a few sites or have quite a bit of traffic then we recommend installing %1$sMatomo On-Premise%2$s separately outside WordPress (it\'s free as well) and use it in combination with the %3$sWP-Matomo%4$s WordPress plugin.
        Your Matomo will then run a lot faster, you can put Matomo on a separate server if needed, and it allows you to make use of additional features such as %5$sRoll-Up Reporting%6$s.', 'matomo'),
        '<a href="https://matomo.org/matomo-on-premise/" target="_blank" rel="noreferrer noopener">', '</a>',
        '<a href="https://wordpress.org/plugins/wp-piwik/" target="_blank" rel="noreferrer noopener">', '</a>',
        '<a href="https://plugins.matomo.org/RollUpReporting" target="_blank" rel="noreferrer noopener">', '</a>'); ?>

        <br /><br /><?php _e('Don\'t want all the hassle of maintaining a Matomo?', 'matomo'); ?> <a href="http://matomo.org/start-free-analytics-trial/" rel="noreferrer noopener" target="_blank"><?php _e( 'Sign up for a free Matomo Cloud trial', 'matomo' ); ?></a>. <?php _e( 'We can migrate all your data onto our Cloud for free. 100% data ownership guaranteed.', 'matomo' ); ?>
    </p>

    <h2><?php _e( 'Matomo blogs', 'matomo' ); ?></h2>
    <ul class="matomo-list">
		<?php
		if (function_exists('get_sites')) {
			foreach ( get_sites() as $site ) {
				/** @var WP_Site $site */
				switch_to_blog( $site->blog_id );
				echo '<li><a href="' . admin_url( 'admin.php?page=matomo-reporting' ) . '">' . $site->blogname . ' (Blog ID: ' . $site->blog_id . ')</a></li>';
				restore_current_blog();
			}
		}
		?>
    </ul>
</div>
