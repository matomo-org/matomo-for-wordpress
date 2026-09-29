/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import fetch from 'node-fetch';
import * as path from 'path';
import * as fs from 'fs';
import MatomoCli from "./apiobjects/matomo.cli.ts";
import { markReleaseInstalled } from './release-sentinel.js';

let latestWordpressVersion: string|undefined;

async function getLatestWordpressVersion() {
  if (!latestWordpressVersion) {
    const response = await fetch('http://api.wordpress.org/core/version-check/1.7/');
    const json = await response.json() as any;
    latestWordpressVersion = json.offers[0].version as string;
  }

  return latestWordpressVersion;
}

class Website {
  private wpNonce: string|undefined;
  private loggedIn: boolean = false;
  private site: string|null = null;
  private wordPressFolderOverride: string|null = null;

  rootUrl() {
    let defaultHostname = 'localhost';
    if (process.env.PORT && process.env.PORT !== '80') {
      defaultHostname = `${defaultHostname}:${process.env.PORT}`;
    }

    return `${process.env.WORDPRESS_URL || `http://${defaultHostname}`}`;
  }

  async getWpFolder() {
    let wordpressVersion = process.env.WORDPRESS_VERSION || 'latest';
    if (wordpressVersion === 'latest') {
      wordpressVersion = await getLatestWordpressVersion();
    }

    const wordpressFolder = this.wordPressFolderOverride || process.env.WORDPRESS_FOLDER || wordpressVersion;
    return wordpressFolder;
  }

  async baseUrl() {
    const wordpressFolder = await this.getWpFolder();
    const wordpressVersionUrlPart = wordpressFolder ? `/${wordpressFolder}` : '';

    let path = wordpressVersionUrlPart;
    if (this.site) {
      path = `${wordpressVersionUrlPart}/${this.site}`;
    }

    return `${this.rootUrl()}${path}`;
  }

  unsetSite() {
    this.site = null;
  }

  switchSite(siteSlug: string) {
    this.site = siteSlug;
  }

  async login(user?: string, pass?: string) {
    if (this.loggedIn) {
      return;
    }

    await browser.setWindowSize(1366, 994);

    const baseUrl = await this.baseUrl();
    await this.retry(3, async () => {
      await browser.url(`${baseUrl}/wp-login.php`);

      await $('#user_login,#wpbody').waitForExist();

      if (await $('#user_login').isExisting()) {
        await browser.execute(
          (l, p) => {
            window.jQuery('#user_login').val(l);
            window.jQuery('#user_pass').val(p);
          },
          user || process.env.WORDPRESS_USER_LOGIN || 'root',
          pass || process.env.WORDPRESS_USER_PASS || 'pass'
        );
        await $('#wp-submit').click();
      }

      await browser.waitUntil(async function () {
        return !!(await browser.execute(function () {
          return window.wpApiSettings?.nonce;
        }));
      }, { timeout: 60000 });
    });
  }

  async logout() {
    const logoutLink = $('#wp-admin-bar-logout a');
    if (await logoutLink.isExisting()) {
        await browser.execute(() => {
            window.jQuery('#wp-admin-bar-logout a')[0].click();
        });

        await $('#user_login').waitForExist({ timeout: 60000 });
    }
  }

  async getWpNonce() {
    if (process.env.WP_APP_PASSWORD) { // TODO: documentation
      return process.env.WP_APP_PASSWORD;
    }

    // assuming local docker-compose environment
    if (!this.wpNonce) {
      const wordpressVersion = process.env.WORDPRESS_VERSION || (await getLatestWordpressVersion());
      const wordpressFolder = process.env.WORDPRESS_FOLDER || wordpressVersion;

      // using process.cwd() as __dirname is not available in wdio for some reason (except probably the conf.ts file)
      const pathToLocalAppPassword = path.join(process.cwd(), 'docker', 'wordpress', wordpressFolder, 'apppassword');
      this.wpNonce = fs.readFileSync(pathToLocalAppPassword).toString('utf-8').trim();
    }

    return this.wpNonce!;
  }

  async deleteAllCookies() {
    await browser.deleteAllCookies();
    this.loggedIn = false;
  }

  async setSiteLanguage(locale: string) {
    const pageUrl = `${await this.baseUrl()}/wp-admin/options-general.php`;
    await browser.url(pageUrl);
    await $('#WPLANG').waitForDisplayed();

    await browser.execute((l) => {
      window.jQuery('#WPLANG').val(l).change();
    }, locale);

    await browser.pause(500);

    let selectedLanguage = await browser.execute(() => window.jQuery('#WPLANG').val());
    if (selectedLanguage !== locale) {
      throw new Error(`unable to set site language input to ${locale}`);
    }

    await $('#submit').click();

    await $('#setting-error-settings_updated').waitForDisplayed();

    selectedLanguage = await browser.execute(() => window.jQuery('#WPLANG').val());
    if (selectedLanguage !== locale) {
      throw new Error(`unable to set site language to ${locale}`);
    }
  }

  async setUserProfileLanguage(locale: string) {
    const pageUrl = `${await this.baseUrl()}/wp-admin/profile.php`;
    await browser.url(pageUrl);
    await $('#locale').waitForDisplayed();

    await browser.execute((l) => {
      window.jQuery('#locale').val(l).change();
    }, locale);

    await $('#submit').click();

    await $('#message.updated').waitForDisplayed();

    const selectedLanguage = await browser.execute(() => window.jQuery('#locale').val());
    if (selectedLanguage !== locale) {
      throw new Error(`unable to set user profile language to ${locale}`);
    }
  }

  overrideWordPressFolder(folder: string) {
    this.wordPressFolderOverride = folder;
  }

  removeWordPressFolderOverride() {
    this.wordPressFolderOverride = null;
  }

  public async retry<R>(times: number, fn: () => Promise<R>, sleepTimeInMsecs: number = 0) {
    while (times > 0) {
      try {
        return await fn();
      } catch (e) {
        --times;

        if (times <= 0) {
          throw e;
        }

        if (sleepTimeInMsecs) {
          // not browser.pause(), so this can also be used from wdio hooks like onPrepare
          // where there is no browser session
          await new Promise((resolve) => setTimeout(resolve, sleepTimeInMsecs));
        }
      }
    }
  }

  async updateMatomoToLatest() {
    await MatomoCli.buildMarketplaceRelease();

    const pathToRelease = process.env.RELEASE_ZIP || MatomoCli.buildRelease();

    await browser.url(`${await this.baseUrl()}/wp-admin/plugin-install.php`);
    await $('a.upload-view-toggle').waitForDisplayed();

    await browser.execute(() => {
      window.jQuery('a.upload-view-toggle')[0].click();
    });
    await browser.pause(250);
    await $('#pluginzip').waitForExist({ timeout: 30000 });

    await $('#pluginzip').setValue(pathToRelease);
    await browser.pause(250);

    await $('#install-plugin-submit').waitForClickable({ timeout: 30000 });
    await browser.execute(() => {
      window.jQuery('#install-plugin-submit')[0].click();
    });

    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return window.jQuery && (
          window.jQuery('p:contains(Plugin updated successfully.)').length > 0 ||
          window.jQuery('p:contains(Plugin downgraded successfully.)').length > 0 ||
          window.jQuery('p:contains(Plugin installed successfully.)').length > 0 ||
          window.jQuery('.update-from-upload-overwrite').length > 0
        );
      });
    }, { timeout: 120000 });

    const isAlreadyExistingPluginPage = await $('.update-from-upload-overwrite');
    if (isAlreadyExistingPluginPage) {
      await browser.execute(() => {
        window.jQuery('.update-from-upload-overwrite')[0].click();
      });

      await browser.waitUntil(async () => {
        return await browser.execute(() => {
          return window.jQuery && (
            window.jQuery('p:contains(Plugin updated successfully.)').length > 0 ||
            window.jQuery('p:contains(Plugin downgraded successfully.)').length > 0 ||
            window.jQuery('p:contains(Plugin installed successfully.)').length > 0
          );
        });
      }, { timeout: 120000 });
    }

    const activateButtonExists = await $('.button=Activate Plugin').isExisting();
    const networkActivateButtonExists = await $('.button=Network Activate').isExisting();
    if (activateButtonExists || networkActivateButtonExists) {
      if ( activateButtonExists ) {
        await $('.button=Activate Plugin').click();
      } else {
        await $('.button=Network Activate').click();
      }

      await browser.waitUntil(async () => {
        return await browser.execute(() => {
          return window.jQuery && window.jQuery('p:contains(Plugin activated.)').length > 0;
        });
      }, { timeout: 120000 });
    } else {
      console.log('No activate button found.');
    }

    // so the main wdio run knows it does not have to install the release itself
    markReleaseInstalled(
      path.join(process.cwd(), 'docker', 'wordpress', await this.getWpFolder(), 'wp-content', 'plugins', 'matomo'),
      pathToRelease,
    );
  }

  /**
   * Appends message to the WordPress debug.log file. Useful for marking where in
   * the logs a specific test starts/ends.
   *
   * @param message
   */
  async log(message: string) {
    if (message.substring(message.length - 1, message.length) !== "\n") {
      message = `${message}\n`;
    }

    const debugLog = path.join(process.cwd(), 'docker', 'wordpress', await this.getWpFolder(), 'wp-content', 'debug.log');
    fs.appendFileSync(debugLog, message);
  }

  async dumpHtml() {
    const html = await browser.execute(() => document.querySelector('html')!.innerHTML);
    console.log('page html:');
    console.log(html);
  }
}

export default new Website();
