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
import { _setGlobal } from '@wdio/globals';
import Config from './config.js';
import { config as wdioConfig } from '../../wdio.conf.js';

async function initWebdriverIo() {
  const options = {
    logLevel: Config.verbose ? 'info' : 'error',
    capabilities: (wdioConfig as any).capabilities[0],
  };

  return webdriverio.remote(options);
}

export default async function generate() {
  const WebdriverAjax = WebdriverAjaxExports.default;
  const interceptService = new WebdriverAjax();

  const browser = await initWebdriverIo();
  _setGlobal('browser', browser);
  _setGlobal('driver', browser);
  _setGlobal('$', (selector) => browser.$(selector));
  _setGlobal('$$', (selector) => browser.$$(selector));

  interceptService.before(null, null, browser);

  for (let i = 0; i < Config.visits; ++i) {
    interceptService.beforeTest();

    const visitor = (await Config.visitors.next()).value;
    try {
      await visitor.execute();
      throw new Error('force');
    } catch (e) {
      console.log(`Failed to execute visits: ${e.message} [URL = ${await browser.getUrl()}]`);
      await browser.saveScreenshot('failure.png');
      throw e;
    }
  }
}
