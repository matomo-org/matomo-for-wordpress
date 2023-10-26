/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import Website from '../website.js';

export default class Page {
  async open(path: string) {
    const baseUrl = await Website.baseUrl();
    return browser.url(`${baseUrl}${path}`);
  }

  async waitForTrackingRequest() {
    await browser.waitUntil(async function() {
      const trackedPageviews = await browser.execute(function () {
        if (!window.Piwik) {
          return 0;
        }

        const tracker = window.Piwik.getAsyncTrackers()[0];
        if (!tracker) {
          return 0;
        }

        return tracker.getNumTrackedPageViews();
      });

      return trackedPageviews > 0;
    });

    await browser.pause(500); // wait for matomo to process the tracking requests
  }
}
