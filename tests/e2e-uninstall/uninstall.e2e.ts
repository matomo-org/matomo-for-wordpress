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
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    await Website.login();
  });

  it('should uninstall and remove all data when the "remove all data" option is enabled', async () => {
    await browser.url(`${await Website.baseUrl()}/plugins.php`);

    await browser.on('dialog', async (dialog) => {
      await dialog.accept();
    });

    await $('#deactivate-matomo').waitForExist();

    await browser.execute(() => {
      window.jQuery('#deactivate-matomo')[0].click();
    });

    await $('#delete-matomo').waitForExist();

    await browser.execute(() => {
      window.jQuery('#delete-matomo')[0].click();
    });
  });

  // TODO
});
