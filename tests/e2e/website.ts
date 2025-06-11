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

const SKIP_SETUP_LINK_SELECTOR = '.woocommerce-profiler-navigation-skip-link,.woocommerce-profile-wizard__footer-link,.woocommerce-profiler-setup-store__button.is-tertiary';

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
    const wordpressVersion = process.env.WORDPRESS_VERSION || (await getLatestWordpressVersion());
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

  async login() {
    if (this.loggedIn) {
      return;
    }

    const baseUrl = await this.baseUrl();
    await this.retry(3, async () => {
      await browser.url(`${baseUrl}/wp-login.php`);

      await $('#user_login').waitForExist();

      await browser.execute(
        (l, p) => {
          window.jQuery('#user_login').val(l);
          window.jQuery('#user_pass').val(p);
        },
        process.env.WORDPRESS_USER_LOGIN || 'root',
        process.env.WORDPRESS_USER_PASS || 'pass'
      );
      await $('#wp-submit').click();

      await browser.waitUntil(async function () {
        return !!(await browser.execute(function () {
          return window.wpApiSettings?.nonce;
        }));
      }, { timeout: 60000 });
    });
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

  async gotoWooCommerceSetupWizard() {
      await browser.url(`${await this.baseUrl()}/wp-admin/admin.php?page=wc-admin&path=%2Fsetup-wizard`);
      const skipSetupLink = $(SKIP_SETUP_LINK_SELECTOR);
      try {
          await skipSetupLink.waitForDisplayed();
      } catch (e) {
          // ignore
      }
      return skipSetupLink;
  }

  /**
   * Misc Notes:
   * - for simpler code here we disable woocommerce's reactified settings page in test-utility-plugin.php
   */
  async setUpWooCommerce() {
    await this.login();

    if (this.isWooCommerceSetup) {
      return;
    }

    const baseUrl = await this.baseUrl();

    await this.gotoWooCommerceSetupWizard();

    // on php 7.2, setting the permalink via wp cli somehow causes the woocommerce setup to break.
    // visiting the permalink settings page, then going back to the setup wizard, fixes this. no
    // idea why.
    await browser.url(`${baseUrl}/wp-admin/options-permalink.php`)
    await $('#permalink-input-plain').waitForExist({ timeout: 30000 });
    const skipSetupLink = await this.gotoWooCommerceSetupWizard();

    const alreadyConfigured = !(await skipSetupLink.isExisting());
    if (alreadyConfigured) {
      console.log('cannot find skip setup link');
      return;
    }

    // get through guided config
    await browser.execute((s) => { window.jQuery(s)[0].click(); }, SKIP_SETUP_LINK_SELECTOR);
    await browser.pause(500);

    const possibleModalButton = $('.woocommerce-usage-modal__actions .is-secondary');
    if (await possibleModalButton.isExisting()) { // woocommerce version that works with php 7.2
      await possibleModalButton.click();
    } else { // latest woocommerce
      await browser.execute(() => {
        window.jQuery('#woocommerce-select-control-0__help')[0].click();
      });

      await browser.execute(() => {
        window.jQuery('.woocommerce-select-control__option[id="woocommerce-select-control__option-0-US:CA"]').click();
      });

      await browser.execute(() => {
        window.jQuery('.woocommerce-profiler-go-to-mystore__button-container > button')[0].click();
      });
    }

    try {
        await browser.waitUntil(async () => {
            const url = await browser.getUrl()
            return /page=wc-admin$/.test(url);
        }, {timeout: 30000});
    } catch (e) {
        console.log(`did not redirect to wc-admin, url is: ${await browser.getUrl()}`);
        throw e;
    }

    await $('.woocommerce-homescreen .woocommerce-experimental-list').waitForDisplayed();

    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return window.jQuery('span:contains(Set up payments)').length > 0
          || window.jQuery('span:contains(You set up payments)').length > 0
          || window.jQuery('span:contains(Get paid)').length > 0;
      });
    }, { timeout: 30000 });

    // enable cash on delivery
    await browser.url(`${baseUrl}/wp-admin/admin.php?page=wc-settings&tab=checkout`);
    await $('div.woocommerce').waitForExist();

    await $('tr[data-gateway_id="cod"] .woocommerce-input-toggle,#woocommerce_cod_enabled,#_wc_offline_payment_methods_group').waitForExist({ timeout: 60000 });

    const isPaymentsSetup = await browser.execute(() => {
      return window.jQuery('tr[data-gateway_id="cod"] .woocommerce-input-toggle--enabled').length > 0
        || window.jQuery('#woocommerce_cod_enabled').is(':checked');
    });

    if (!isPaymentsSetup) {
      await this.retry(3, async () => {
        const isWooCommerceCodInputFound = await $('#woocommerce_cod_enabled').isExisting();
        const isWoocommerceCodToggleFound = await $('tr[data-gateway_id="cod"] .woocommerce-input-toggle').isExisting();
        const isWoocommerceTakeOfflinePaymentsFound = await $('#_wc_offline_payment_methods_group').isExisting();

        const html = await browser.execute(() => document.querySelector('html')!.innerHTML);

        if (isWoocommerceTakeOfflinePaymentsFound) {
            await $('#_wc_offline_payment_methods_group').click();

            await browser.waitUntil(async () => {
                return await browser.execute(() => window.jQuery('.woocommerce-list__item-title:contains(Cash on delivery)').length > 0);
            }, { timeout: 30000 });

            await browser.execute(() => {
                window.jQuery('.woocommerce-list__item-title:contains(Cash on delivery)').closest('.woocommerce-list__item-inner').find('a.is-primary')[0].click();
            });

            await browser.waitUntil(async () => {
                return await browser.execute(() => {
                    return window.jQuery('.woocommerce-list__item-inner a.is-secondary').length > 0;
                });
            }, { timeout: 30000 });
        } else if (isWooCommerceCodInputFound || html.includes('#woocommerce_cod_enabled')) {
          await $('label[for="woocommerce_cod_enabled"]').click();
          await browser.execute(() => window.jQuery('.woocommerce-save-button')[0].click());
          await browser.waitUntil(async () => {
            return await browser.execute(() => window.jQuery('#message:contains(Your settings have been saved)').length > 0);
          }, { timeout: 60000 });
        } else if (isWoocommerceCodToggleFound || html.includes('data-gateway_id="cod"')) {
          await browser.execute(() => {
            window.jQuery('tr[data-gateway_id="cod"] .woocommerce-input-toggle--disabled').closest('a')[0].click();
          });

          try {
            await $('tr[data-gateway_id="cod"] .woocommerce-input-toggle--enabled').waitForExist({ timeout: 90000 });
          } catch (e) {
            await this.dumpHtml();
            throw e;
          }

          if (await $('.woocommerce-save-button').isExisting()) {
            await browser.execute(() => {
              window.jQuery('.woocommerce-save-button')[0].click();
            });

            try {
              await browser.waitUntil(async () => {
                return await browser.execute(() => window.jQuery('.woocommerce-save-button[disabled],tr[data-gateway_id="cod"] .woocommerce-input-toggle--enabled').length > 0);
              }, { timeout: 60000 });
            } catch (e) {
              await this.dumpHtml();
              throw e;
            }
          } else {
            throw new Error('unknown page html in woocommerce setup');
          }
        } else {
            throw new Error('unknown page html in woocommerce setup');
        }
      });
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
          await browser.pause(sleepTimeInMsecs);
        }
      }
    }
  }

  async updateMatomoToLatest() {
    const pathToRelease = process.env.RELEASE_ZIP || MatomoCli.buildRelease();

    await browser.url(`${await this.baseUrl()}/wp-admin/plugin-install.php`);
    await $('a.upload-view-toggle').waitForDisplayed();

    await browser.execute(() => {
      window.jQuery('a.upload-view-toggle')[0].click();
    });
    await browser.pause(250);
    await $('#pluginzip').waitForClickable();

    await $('#pluginzip').setValue(pathToRelease);
    await browser.pause(250);

    await $('#install-plugin-submit').waitForClickable();
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
    if (activateButtonExists) {
      await $('.button=Activate Plugin').click();

      await browser.waitUntil(async () => {
        return await browser.execute(() => {
          return window.jQuery && window.jQuery('p:contains(Plugin activated.)').length > 0;
        });
      }, { timeout: 120000 });
    }
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
