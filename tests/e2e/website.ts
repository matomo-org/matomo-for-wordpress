/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import * as path from 'path';
import * as fs from 'fs';
import {browser} from "@wdio/globals";

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

    return `${process.env.WORDPRESS_URL || 'http://localhost:3000'}${wordpressVersionUrlPart}`
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
    }, { timeout: 40000 });
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

    await browser.url(`${baseUrl}/wp-admin/admin.php?page=wc-admin`);

    const skipSetupLink = $('.woocommerce-profiler-navigation-skip-link,.woocommerce-profile-wizard__footer-link');
    try {
      await skipSetupLink.waitForDisplayed();
    } catch (e) {
      // ignore
    }

    const alreadyConfigured = !(await skipSetupLink.isExisting());
    if (alreadyConfigured) {
      console.log(await browser.execute(() => document.body.innerHTML));
      throw new Error('should not happen in CI');
      return;
    }

    // get through guided config
    await browser.execute(() => {
      window.jQuery('.woocommerce-profiler-navigation-skip-link,.woocommerce-profile-wizard__footer-link')[0].click();
    })
    await browser.pause(500);

    let isWooCommerce7 = false;

    const possibleModalButton = $('.woocommerce-usage-modal__actions.is-secondary');
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
      return /page=wc-admin/.test(url);
    });

    // enable cash on delivery
    if (isWooCommerce7) {
      await browser.execute(() => {
        window.jQuery('span:contains(Set up payments)').closest('li').click();
      });
      await $('.woocommerce-task-payment-cod').waitForExist();
    }

    await browser.execute(() => {
      window.jQuery('.woocommerce-task-payment-cod .woocommerce-task-payment__action')[0].click();
    });

    await browser.pause(500);

    this.isWooCommerceSetup = true;
  }

  async deleteAllCookies() {
    await browser.deleteAllCookies();
    this.loggedIn = false;
  }
}

export default new Website();
