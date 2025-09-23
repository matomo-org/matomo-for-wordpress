/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import 'dotenv/config';
import * as webdriverio from 'webdriverio';
import WebdriverAjaxExports from 'wdio-intercept-service';
import WebdriverImageComparisonExports from 'wdio-image-comparison-service';
import {_setGlobal, browser} from '@wdio/globals';
import Config from './config.js';
import { config as wdioConfig } from '../../wdio.conf.js';
import PluginsAdminPage from '../e2e/pageobjects/wp-admin/plugins-admin.page.js';
import Website from '../e2e/website.js';

async function initWebdriverIo() {
  const options = {
    logLevel: Config.verbose ? 'debug' : 'error',
    capabilities: (wdioConfig as any).capabilities[0],
  };

  return webdriverio.remote(options);
}

async function activateWpStatistics() {
  await PluginsAdminPage.open();
  await $('[data-slug="wp-statistics"]').waitForExist({ timeout: 60000 });
  if (await $('#activate-wp-statistics').isExisting()) {
    await $('#activate-wp-statistics').click();
    await $('#deactivate-wp-statistics').waitForExist({ timeout: 60000 });
  }
}

export default async function generate() {
  const WebdriverAjax = WebdriverAjaxExports.default;
  const interceptService = new WebdriverAjax();

  const WebdriverImageComparisonService = WebdriverImageComparisonExports.default;
  let wdioImageComparisonService = new WebdriverImageComparisonService({});

  const browser = await initWebdriverIo();
  _setGlobal('browser', browser);
  _setGlobal('driver', browser);
  _setGlobal('$', (selector) => browser.$(selector));
  _setGlobal('$$', (selector) => browser.$$(selector));

  wdioImageComparisonService.defaultOptions.autoSaveBaseline = true
  browser.defaultOptions = wdioImageComparisonService.defaultOptions;
  wdioImageComparisonService.folders.actualFolder = '';
  browser.folders = wdioImageComparisonService.folders;
  wdioImageComparisonService.before(browser.capabilities)
  interceptService.before(null, null, browser);

  let visitor;

  try {
    await Website.setUpWooCommerce();
    await activateWpStatistics();

    for (let i = 0; i < Config.visits; ++i) {
      interceptService.beforeTest();

      visitor = (await Config.visitors.next()).value;
      await visitor.execute();
    }
  } catch (e) {
    console.log(`Failed to execute visits: ${e.message} [URL = ${await browser.getUrl()}]`);
    await browser.saveFullPageScreen('failure');
    throw e;
  } finally {
    await browser.deleteSession();
  }
}
