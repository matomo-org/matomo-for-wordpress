/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from '../page.js';

class MwpSettingsPage extends Page {
  open() {
    return super.open('/wp-admin/admin.php?page=matomo-settings');
  }

  async enableTagManagerTracking() {
    await browser.execute(() => {
      window.jQuery('#track_mode').val('tagmanager').change();
    });

    await browser.execute(() => {
      window.jQuery('tr.matomo-track-option-tagmanager input[type="checkbox"]').first().prop('checked', true);
    });

    throw new Error('force failure');

    await browser.execute(() => {
      window.jQuery('.matomo-tracking-form .submit > input').click();
    });

    await browser.pause(1000);
  }

  async disableTagManagerTracking() {
    await browser.execute(() => {
      window.jQuery('#track_mode').val('default').change();
    });

    await browser.execute(() => {
      window.jQuery('.matomo-tracking-form .submit > input').click();
    });

    await browser.pause(1000);
  }
}

export default new MwpSettingsPage();

// /wp-admin/admin.php?page=matomo-settings
