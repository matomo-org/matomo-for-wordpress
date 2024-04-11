/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import {browser, $, expect} from '@wdio/globals';
import fetch from 'node-fetch';
import * as path from 'path';
import * as fs from 'fs';

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
  private isWooCommerceSetup: boolean = false;

  async baseUrl() {
    const wordpressVersion = process.env.WORDPRESS_VERSION || (await getLatestWordpressVersion());
    const wordpressFolder = process.env.WORDPRESS_FOLDER || wordpressVersion;
    const wordpressVersionUrlPart = wordpressFolder ? `/${wordpressFolder}` : '';

    let defaultHostname = 'localhost';
    if (process.env.PORT && process.env.PORT !== '80') {
      defaultHostname = `${defaultHostname}:${process.env.PORT}`;
    }

    return `${process.env.WORDPRESS_URL || `http://${defaultHostname}`}${wordpressVersionUrlPart}`
  }

  async login() {
    if (this.loggedIn) {
      return;
    }

    const baseUrl = await this.baseUrl();
    await browser.url(`${baseUrl}/wp-login.php`);

    await $('#user_login').setValue(process.env.WORDPRESS_USER_LOGIN || 'root');
    await $('#user_pass').setValue(process.env.WORDPRESS_USER_PASS || 'pass');
    await $('#wp-submit').click();

    await browser.waitUntil(async function () {
      return !!(await browser.execute(function () {
        return window.wpApiSettings?.nonce;
      }));
    }, { timeout: 60000 });
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

  async setUpWooCommerce() {
    await this.login();

    if (this.isWooCommerceSetup) {
      return;
    }

    const baseUrl = await this.baseUrl();

    await browser.url(`${baseUrl}/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`);

    const skipSetupLink = $('.woocommerce-profiler-navigation-skip-link,.woocommerce-profile-wizard__footer-link');
    try {
      await skipSetupLink.waitForDisplayed();
    } catch (e) {
      // ignore
    }

    const alreadyConfigured = !(await skipSetupLink.isExisting());
    if (alreadyConfigured) {
      return;
    }

    // get through guided config
    await browser.execute(() => {
      window.jQuery('.woocommerce-profiler-navigation-skip-link,.woocommerce-profile-wizard__footer-link')[0].click();
    })
    await browser.pause(500);

    let isWooCommerce7 = false;

    const possibleModalButton = $('.woocommerce-usage-modal__actions .is-secondary');
    if (await possibleModalButton.isExisting()) { // woocommerce version that works with php 7.2
      isWooCommerce7 = true;

      await possibleModalButton.click();
    } else { // latest woocommerce
      await $('#woocommerce-select-control-0__help').click();

      await browser.execute(() => {
        window.jQuery('.woocommerce-select-control__option[id="woocommerce-select-control__option-0-US:CA"]').click();
      });

      await $('.woocommerce-profiler-go-to-mystore__button-container > button').click();
    }

    await browser.waitUntil(async () => {
      const url = await browser.getUrl()
      return /page=wc-admin$/.test(url);
    });

    await $('.woocommerce-homescreen .woocommerce-experimental-list').waitForDisplayed();

    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return window.jQuery('span:contains(Set up payments)').length > 0
          || window.jQuery('span:contains(You set up payments)').length > 0;
      });
    });

    // enable cash on delivery
    const isPaymentsSetup = await browser.execute(() => {
      return window.jQuery('span:contains(You set up payments)').length > 0;
    });

    if (!isPaymentsSetup) {
      await browser.execute(() => {
        window.jQuery('span:contains(Set up payments)').closest('li')[0].click();
      });

      await $('.woocommerce-task-payment-cod').waitForExist();

      const numAttempts = 3;
      for (let i = 0; i < numAttempts; ++i) {
        await browser.execute(() => {
          window.jQuery('.woocommerce-task-payment-cod .woocommerce-task-payment__action')[0].click();
        });

        try {
          await browser.waitUntil(() => {
            return browser.execute(() => {
              return window.jQuery('.woocommerce-task-payment-cod .woocommerce-task-payment__action:contains(Manage)').length > 0;
            });
          });

          break;
        } catch (e) {
          if (i + 1 >= numAttempts) {
            throw e;
          }
        }
      }
    }

    this.isWooCommerceSetup = true;
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

    await $('#submit').click();

    await $('#setting-error-settings_updated').waitForDisplayed();

    const selectedLanguage = await browser.execute(() => window.jQuery('#WPLANG').val());
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
}

export default new Website();
