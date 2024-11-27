/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

import { browser, $, expect } from '@wdio/globals';
import Website from '../e2e/website.js';

describe('MWP Uninstall', () => {
  before(async () => {
    await Website.login();
  });

  it('should uninstall and remove all data when the "remove all data" option is enabled', async () => {
    await browser.url(`${await Website.baseUrl()}/wp-admin/plugins.php`);

    await $('#deactivate-matomo').waitForExist({ timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('#deactivate-matomo')[0].click();
    });

    await $('#delete-matomo').waitForExist({ timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('#delete-matomo')[0].click();
    });

    await browser.pause(300); // wait for alert

    try {
      await browser.acceptAlert();
    } catch (e) {
      // pass
    }

    await $('#matomo-deleted').waitForExist({ timeout: 180000 });
  });
});
