/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import fetch from 'node-fetch';
import * as semver from 'semver';
import Website from './website.js';
import DashboardPage from './pageobjects/wp-admin/dashboard.page.js';
import PluginsAdminPage, { MATOMO_UPDATE_ROW_SELECTOR } from './pageobjects/wp-admin/plugins-admin.page.js';
import UpdatePluginPage, { UPDATE_RESULT_SELECTOR } from './pageobjects/wp-admin/update-plugin.page.js';
import UsersAdminPage from './pageobjects/wp-admin/users-admin.page.js';
import MwpDiagnosticsPage from './pageobjects/mwp-admin/diagnostics.page.js';

const FAKE_UPDATE_VERSION = '6.0.0';
const BLOCKED_NOTICE_SELECTOR = '#matomo-minimumrequirementsblocked';
const BLOCKED_MESSAGE = 'This update cannot be installed because your server does not meet its minimum requirements';

async function callTestUtility(action: string, enable: boolean) {
  await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
      action,
      enable: enable ? '1' : '',
    }),
  });
}

async function normalizeCurrentVersions() {
  await browser.execute(() => {
    window.jQuery('.plugin-update-tr, #wpbody-content .wrap, .matomo-notice').each(function () {
      const el = window.jQuery(this);
      el.html(el.html().replace(/using (PHP|MySQL|MariaDB) \d+[\d.]*/g, 'using $1 REMOVED'));
    });
  });
}

describe('MWP Admin > Matomo 6 Update Block', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  // matomo 6 requires PHP 8.1, so only the older matrix entry has an unmet requirement
  const isBlocked = semver.lt(`${process.env.PHP_VERSION}.0`, '8.1.0');

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await Website.login();
    await callTestUtility('matomo_test_set_fake_plugin_update', true);
  });

  after(async () => {
    await callTestUtility('matomo_test_set_fake_plugin_update', false);
    await callTestUtility('matomo_test_set_fake_blocked_version', false);
  });

  it('should offer the fake Matomo 6 update in the plugins list', async () => {
    await PluginsAdminPage.open();

    expect(await PluginsAdminPage.hasUpdateAvailable()).toBeTruthy();
    expect(await PluginsAdminPage.updateRowText()).toContain(FAKE_UPDATE_VERSION);
  });

  it('should explain in the plugins list why the update cannot be installed', async function () {
    if (!isBlocked) {
      this.skip();
      return;
    }

    await PluginsAdminPage.open();

    const rowText = await PluginsAdminPage.updateRowText();
    expect(rowText).toContain(BLOCKED_MESSAGE);
    expect(rowText).toContain('PHP 8.1 or higher is required');

    await normalizeCurrentVersions();
    await expect(
      await PluginsAdminPage.checkElement(
        MATOMO_UPDATE_ROW_SELECTOR,
        `mwp-admin.update-block.plugins-row.${process.env.PHP_VERSION}${trunkSuffix}`
      )
    ).toBeLessThan(0.1);
  });

  it('should offer the update normally when the requirements are met', async function () {
    if (isBlocked) {
      this.skip();
      return;
    }

    await PluginsAdminPage.open();

    expect(await PluginsAdminPage.updateRowText()).not.toContain('This update cannot be installed');
    expect(await PluginsAdminPage.hasElement(`${MATOMO_UPDATE_ROW_SELECTOR} .update-link`)).toBeTruthy();

    // deliberately not clicking: the guard would let this through and the dummy archive
    // would really replace the installed plugin.
  });

  it('should fail the update in place with a message explaining the requirements', async function () {
    if (!isBlocked) {
      this.skip();
      return;
    }

    await PluginsAdminPage.open();
    await PluginsAdminPage.clickUpdateNow();

    await PluginsAdminPage.waitForUpdateRowText('Update failed');

    const rowText = await PluginsAdminPage.updateRowText();
    expect(rowText).toContain(`Matomo Analytics ${FAKE_UPDATE_VERSION} cannot be installed`);
    expect(rowText).toContain('PHP 8.1 or higher is required');
    expect(rowText).toContain('Please ask your hosting provider to update your server');
    expect(await PluginsAdminPage.hasRequirementsFaqLinkInUpdateRow()).toBeTruthy();
  });

  it('should fail the update on the update page with a message explaining the requirements', async function () {
    if (!isBlocked) {
      this.skip();
      return;
    }

    await PluginsAdminPage.open();
    const updateNowUrl = await PluginsAdminPage.updateNowUrl();
    await UpdatePluginPage.open(updateNowUrl);

    await UpdatePluginPage.waitForText('cannot be installed because your server');

    const pageText = await UpdatePluginPage.content();
    expect(pageText).toContain(`Matomo Analytics ${FAKE_UPDATE_VERSION} cannot be installed`);
    expect(pageText).toContain('PHP 8.1 or higher is required');
    expect(pageText).toContain('Please ask your hosting provider to update your server');
    expect(await UpdatePluginPage.hasRequirementsFaqLink()).toBeTruthy();

    await normalizeCurrentVersions();
    await expect(
      await UpdatePluginPage.checkElement(
        UPDATE_RESULT_SELECTOR,
        `mwp-admin.update-block.update-failed.${process.env.PHP_VERSION}${trunkSuffix}`
      )
    ).toBeLessThan(0.1);
  });

  it('should leave the installed plugin untouched after a blocked update', async function () {
    if (!isBlocked) {
      this.skip();
      return;
    }

    await PluginsAdminPage.open();

    // if the guard ever stops firing, the dummy archive really installs and this catches it
    expect(await PluginsAdminPage.installedVersion()).not.toEqual(FAKE_UPDATE_VERSION);
    expect(await PluginsAdminPage.isMatomoActive()).toBeTruthy();
  });

  describe('the notice shown when the installed version cannot run', () => {
    before(async function () {
      if (!isBlocked) {
        this.skip();
        return;
      }

      await callTestUtility('matomo_test_set_fake_blocked_version', true);
    });

    after(async () => {
      await callTestUtility('matomo_test_set_fake_blocked_version', false);
    });

    it('should look correct', async () => {
      await DashboardPage.open();

      await normalizeCurrentVersions();
      await expect(
        await DashboardPage.checkElement(
          BLOCKED_NOTICE_SELECTOR,
          `mwp-admin.update-block.disabled-notice.${process.env.PHP_VERSION}${trunkSuffix}`
        )
      ).toBeLessThan(0.1);
    });

    it('should not be dismissible on a Matomo admin page', async () => {
      await MwpDiagnosticsPage.open();

      expect(await MwpDiagnosticsPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeTruthy();
      expect(await MwpDiagnosticsPage.elementHasClass(BLOCKED_NOTICE_SELECTOR, 'is-dismissible')).toBeFalsy();
    });

    it('should not be dismissible on the plugins page', async () => {
      await PluginsAdminPage.open();

      expect(await PluginsAdminPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeTruthy();
      expect(await PluginsAdminPage.elementHasClass(BLOCKED_NOTICE_SELECTOR, 'is-dismissible')).toBeFalsy();
    });

    it('should be dismissible on other admin pages', async () => {
      await DashboardPage.open();

      expect(await DashboardPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeTruthy();
      expect(await DashboardPage.elementHasClass(BLOCKED_NOTICE_SELECTOR, 'is-dismissible')).toBeTruthy();
    });

    it('should stay dismissed on every other admin page once dismissed', async () => {
      await DashboardPage.open();

      await browser.execute((s) => {
        window.jQuery(`${s} .notice-dismiss`)[0].click();
      }, BLOCKED_NOTICE_SELECTOR);

      await browser.waitUntil(
        async () => !(await DashboardPage.hasElement(BLOCKED_NOTICE_SELECTOR)),
        { timeout: 30000 }
      );
      await browser.pause(1000); // let the dismissal ajax finish

      await UsersAdminPage.open();
      expect(await UsersAdminPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeFalsy();

      // always visible on plugin management pages
      await PluginsAdminPage.open();
      expect(await PluginsAdminPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeTruthy();
      expect(await PluginsAdminPage.elementHasClass(BLOCKED_NOTICE_SELECTOR, 'is-dismissible')).toBeFalsy();

      // always shown in mwp admin pages
      await MwpDiagnosticsPage.open();
      expect(await MwpDiagnosticsPage.hasElement(BLOCKED_NOTICE_SELECTOR)).toBeTruthy();
    });
  });
});
