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
    const result = await browser.url(`${baseUrl}${path}`);

    await this.addStylesToPage(`* {
      scrollbar-width: none !important;
    }

    *::-webkit-scrollbar {
      display: none;
    }`);

    // move mouse away from screen
    await $('body').moveTo({ xOffset: 0, yOffset: 0 });

    return result;
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

  async addStylesToPage(css: string) {
    await browser.execute(function (c) {
      document.head.insertAdjacentHTML('beforeend', `<style>${c}</style>`);
    } as any, css);
  }
}
