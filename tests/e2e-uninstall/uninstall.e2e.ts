/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

import { browser, $, expect } from '@wdio/globals';
import Website from '../e2e/website.js';
import fetch from 'node-fetch';

describe('MWP Uninstall', () => {
  before(async () => {
    await Website.login();
  });

  async function getTablesInstalled(): Promise<any> {
    let result: any = await fetch(`${await Website.rootUrl()}/matomo-for-wordpress/tests/e2e-uninstall/get-matomo-tables.php`);
    result = await result.text();
    try {
      result = JSON.parse(result);
    } catch (e) {
      throw new Error(`Failed to parse get-matomo-tables.php response: ${result}`);
    }
    return result;
  }

  it('should uninstall and remove all data when the "remove all data" option is enabled', async () => {
    let tablesBeforeUninstall = await getTablesInstalled();
    tablesBeforeUninstall = tablesBeforeUninstall.filter((t: string) => t.includes('matomo'));
    expect(tablesBeforeUninstall.length).toBeGreaterThan(0); // before uninstalling, check there are tables with "matomo" in the name

    await browser.url(`${await Website.baseUrl()}/wp-admin/plugins.php`);

    await $('#deactivate-matomo').waitForExist({ timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('#deactivate-matomo')[0].click();
    });

    await $('#delete-matomo').waitForExist({ timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('#delete-matomo')[0].click();
    });

    await browser.pause(500); // wait for alert

    try {
      // case when a confirm modal is shown
      await browser.acceptAlert();
      await $('#matomo-deleted').waitForExist({ timeout: 180000 });
    } catch (e) {
      // case when the user is redirected to a page with a <form>
      await $('form #submit').waitForExist({ timeout: 30000 });
      await $('form #submit').click();

      await $('table.plugins').waitForExist({ timeout: 180000 });
      await browser.pause(30000);
    }

    // check that no matomo table exists in the database
    let result = await getTablesInstalled();

    expect(result.length).toBeGreaterThan(0); // sanity check

    result = result.filter((t: string) => t.includes('matomo'));
    expect(result).toEqual([]); // check there are no tables with "matomo" in the name
  });
});
