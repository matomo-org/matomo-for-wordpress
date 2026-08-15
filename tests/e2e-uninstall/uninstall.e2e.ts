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
    let result: any = await fetch(`${await Website.rootUrl()}/matomo-for-wordpress/tests/e2e-uninstall/get-matomo-tables.php?multi=0`);
    result = await result.text();
    try {
      result = JSON.parse(result);
    } catch (e) {
      throw new Error(`Failed to parse get-matomo-tables.php response: ${result}`);
    }
    return result;
  }

  it('should uninstall and remove all data when the "remove all data" option is enabled', async () => {
    let tablesBeforeUninstall = (await getTablesInstalled()).tables;
    tablesBeforeUninstall = tablesBeforeUninstall.filter((t: string) => t.includes('matomo'));
    expect(tablesBeforeUninstall.length).toBeGreaterThan(0); // before uninstalling, check there are tables with "matomo" in the name

    await browser.url(`${await Website.baseUrl()}/wp-admin/plugins.php`);

    await $('#deactivate-matomo').waitForExist({ timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('#deactivate-matomo')[0].click();
    });

    await $('#delete-matomo').waitForExist({ timeout: 60000 });

    try {
      await Website.retry(3, async () => {
        await browser.execute(() => {
          if (window.jQuery('#delete-matomo').length) {
            window.jQuery('#delete-matomo')[0].click();
          }
        });

        await browser.pause(500); // wait for alert

        // case when a confirm modal is shown
        try {
          await browser.acceptAlert();
        } catch (e) {
          // ignore
        }

        const hasDelete = await browser.execute(() => window.jQuery('#delete-matomo'));
        if (hasDelete) {
          const isDeleting = await browser.execute(() => {
            const html = window.jQuery('#delete-matomo').html();
            return html && html.includes('Deleting');
          });

          if (!isDeleting) {
            throw new Error('clicking delete did nothing');
          }
        }
      }, 500);
    } catch (e) {
      if ((e as Error).message !== 'clicking delete did nothing') {
        throw e;
      }

      let deleteUrl = await browser.execute(() => window.jQuery('#delete-matomo').attr('href'));
      let formExists = await $('form #submit').isExisting();

      if (deleteUrl) {
        deleteUrl = `${await Website.baseUrl()}/wp-admin/${deleteUrl}`;
        console.log(`attempting to visit plugin delete URL manually (URL = ${deleteUrl})`);
        await browser.url(deleteUrl);
      } else if (!formExists) {
        await Website.dumpHtml();
        throw new Error('cannot find delete Matomo link URL');
      }
    }

    await $('#matomo-deleted,form #submit').waitForExist({ timeout: 180000 });

    const formExists = await $('form #submit').isExisting();
    if (formExists) {
      // case when the user is redirected to a page with a <form>
        await $('form #submit').waitForExist({ timeout: 30000 });
        await $('form #submit').click();

        await $('table.plugins').waitForExist({ timeout: 180000 });
    }

    // wait until tables have been dropped
    let result = await getTablesInstalled();
    try {
      await browser.waitUntil(async () => {
        result = await getTablesInstalled();
        return result.tables instanceof Array
          && result.tables.filter((t: string) => t.includes('matomo')).length === 0;
      }, { timeout: 60000, interval: 1000 });
    } catch (e) {
      // ignore, the assertions below will report what is still there
    }

    expect(result.tables).toBeInstanceOf(Array); // sanity check

    let tables = result.tables;
    tables = tables.filter((t: string) => t.includes('matomo'));
    expect(tables).toEqual([]); // check there are no tables with "matomo" in the name
  });
});
