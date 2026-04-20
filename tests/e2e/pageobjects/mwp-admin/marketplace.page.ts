/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser, expect } from '@wdio/globals';
import * as fs from 'fs';
import * as path from 'path';
import * as url from 'url';
import fetch from 'node-fetch';
import MwpPage from './page.js';

const dirname = path.dirname(url.fileURLToPath(import.meta.url));

const DOWNLOADS_DIR = path.join(dirname, '..', '..', 'downloads');

class MwpMarketplaceSetupWizard {
  async downloadPlugin(): Promise<string> {
    const downloadUrl = await browser.execute(() => window.jQuery('.download-plugin').attr('href'));
    if (!downloadUrl) {
      throw new Error('could not extract marketplace download URL from page');
    }

    const downloadPath = path.join(DOWNLOADS_DIR, path.basename(downloadUrl));

    await $('.download-plugin').click();
    await browser.waitUntil(() => fs.existsSync(downloadPath), { timeout: 30000 });

    return downloadPath;
  }

  async goToPluginsAdmin(): Promise<void> {
    await $('.open-plugin-upload').click();

    await browser.waitUntil(async () => {
      await browser.pause(2000);
      try {
        await browser.switchWindow(/\/wp-admin\/plugin-install\.php\?tab=upload/);
        return true;
      } catch (e) {
        return false;
      }
    }, { timeout: 15000 });

    await $('#pluginzip').waitForDisplayed();
  }

  async uploadPluginAndActivate(pathToPlugin: string): Promise<void> {
    await $('#pluginzip').setValue(pathToPlugin);
    await browser.pause(500);
    await $('#install-plugin-submit').click();
    await $('.button=Activate Plugin').waitForDisplayed({ timeout: 30000 });

    await $('.button=Activate Plugin').click();
    await browser.waitUntil(() => {
      return browser.execute(() => /\/wp-admin\/plugins\.php$/.test(window.location.pathname));
    });

    await browser.closeWindow();
    await browser.switchWindow(/page=matomo-marketplace/);
  }

  async waitForReload(): Promise<void> {
    await $('.matomo-plugin-card').waitForDisplayed({ timeout: 120000 });
  }
}

class MwpMarketplacePage extends MwpPage {
  readonly setupWizard = new MwpMarketplaceSetupWizard();

  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-marketplace');
  }

  async openInstallPluginsTab() {
    await $('a.nav-tab=Install Plugins').click();

    await $('.matomo-plugin-card,.matomo-marketplace-wizard').waitForExist({ timeout: 120000 });

    if (await $('.matomo-plugin-card').isExisting()) {
      // remove most plugins so the screenshot will stay the same over time
      await this.removeThirdPartyPlugins();
    }
  }

  async removeThirdPartyPlugins() {
    await this.addStylesToPage(`
      .matomo-plugin-card[data-plugin-slug="Force SSL"] {
        display: none !important;
      }

      .matomo-plugin-card:not([data-developer="matomo-org"]) {
        display: none !important;
      }
    `);
  }

  async openSubscriptionsTab() {
    await $('a.nav-tab=Premium Features').click();
  }

  async setSubscriptionLicense(license: string) {
    if (!license) {
      throw new Error('no license specified in TEST_SHOP_LICENSE environment var, cannot run test');
    }

    // just for screenshots, make sure the license does not display
    await browser.execute(() => {
      window.jQuery('#license-key-input').attr('type', 'password');
    });

    await browser.execute((l) => {
      window.jQuery('#license-key-input').val(l);
    }, license);

    await $('#wpbody-content .activate-license').click();

    await $('#wpbody-content .matomo-marketplace-install-plugins').waitForDisplayed({ timeout: 120000 });
  }

  async installPlugin(plugin: string) {
    await browser.execute((p) => {
      window.jQuery('.matomo-plugin-card[data-plugin-slug="SEOWebVitals"] .cta-container button')[0].click();
    }, plugin);

    await $('#wpbody-content p a.button-primary').waitForDisplayed({ timeout: 30000 });
  }

  async activateInstalledPlugin() {
    await $('#wpbody-content p a.button-primary').click();
    await $('table.plugins').waitForDisplayed({ timeout: 30000 });
  }

  async showToActivatePlugins() {
    await $('.subsubsub li.activate > a').click();
    await $('.subsubsub li.activate > a.current').waitForDisplayed({ timeout: 30000 });
  }

  async bulkInstallMatomoPlugins() {
    await $('[bulk-install-nonce]').waitForExist();

    const matomoPlugins = await browser.execute(() => {
      return [...window.jQuery('.matomo-plugin-card[data-developer="matomo-org"]')]
        .map((e) => ({
          slug: e.getAttribute('data-plugin-slug'),
          name: window.jQuery(e).find('.card-title').text().replace('›', '').trim(),
        }));
    });

    const currentPageUrl = await browser.execute(() => window.location.pathname);

    const nonce = await browser.execute(() => {
      return window.jQuery('#matomo-marketplace-for-wordpress').data('bulk-install-nonce');
    });

    const body = new URLSearchParams({
      'tgmpa-page': 'matomo-marketplace',
      plugin_status: 'all',
      _wpnonce: nonce,
      _wp_http_referer: currentPageUrl,
      action: 'tgmpa-bulk-install',
      bulk_action: 'Apply',
      action2: 'tgmpa-bulk-install',
    });
    matomoPlugins.forEach((p) => {
      body.append('plugin[]', p.slug);
    });

    const response = await fetch('http://localhost/6.9.4/wp-admin/admin.php?page=matomo-marketplace&tab=install', {
      method: 'POST',
      body,
    });

    const responseBody = await response.text();
    const installedPlugins = [...responseBody.matchAll(/<p>\s*(.*?) installed successfully/g)].map(g => g[1]);
    installedPlugins.sort();

    const allPluginsName = matomoPlugins.map(p => p.name);
    allPluginsName.sort();

    expect(installedPlugins).toEqual(allPluginsName);

    await browser.refresh(); // for new nonce values

    return matomoPlugins;
  }

  async bulkActivateMatomoPlugins(installedPlugins: { name: string, slug: string }[]) {
    const currentPageUrl = await browser.execute(() => window.location.pathname);

    await $('[bulk-activate-nonce]').waitForExist();

    const nonce = await browser.execute(() => {
      return window.jQuery('#matomo-marketplace-for-wordpress').data('bulk-activate-nonce');
    });

    const body = new URLSearchParams({
      'tgmpa-page': 'matomo-marketplace',
      plugin_status: 'all',
      _wpnonce: nonce,
      _wp_http_referer: currentPageUrl,
      action: 'tgmpa-bulk-activate',
      bulk_action: 'Apply',
      action2: 'tgmpa-bulk-activate',
    });
    installedPlugins.forEach((p) => {
      body.append('plugin[]', p.slug);
    });

    const response = await fetch('http://localhost/6.9.4/wp-admin/admin.php?page=matomo-marketplace&tab=install', {
      method: 'POST',
      body,
    });

    const responseBody = await response.text();
    let activatedPlugins = (/<p>\s*The following plugins were activated successfully: (.*?)\.\s*<\/p>/g.exec(responseBody) || ['', ''])[1]
      .replace(/<\/?strong>/g, '')
      .split(/\band\b/g)
      .map(p => p.trim());
    activatedPlugins.sort();

    const allPluginsName = installedPlugins.map(p => p.name);
    allPluginsName.sort();

    expect(activatedPlugins).toEqual(allPluginsName);

    await browser.refresh(); // for new nonce values
  }
}

export default new MwpMarketplacePage();
