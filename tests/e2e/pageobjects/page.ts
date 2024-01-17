/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import {$, browser} from '@wdio/globals';
import Website from '../website.js';
import GlobalSetup from '../global-setup.js';

export default class Page {
  async open(path: string) {
    const baseUrl = await Website.baseUrl();
    const result = await browser.url(`${baseUrl}${path}`);

    await this.addStylesToPage(`
    * {
      scrollbar-width: none !important;
    }

    *::-webkit-scrollbar {
      display: none;
    }

    html.disable-hover * {
      pointer-events: none !important;
    }

    html.disable-modal-scroll .modal {
      overflow-y: none !important;
    }
    `);

    return result;
  }

  async enableHoverStyles() {
    await browser.execute(() => {
      $('html').addClass('disable-hover');
    });
  }

  async disableHoverStyles() {
    await browser.execute(() => {
      $('html').removeClass('disable-hover');
    });
  }

  async disableModalScroll() {
    await browser.execute(() => {
      $('html').addClass('disable-modal-scroll');
    });
  }

  async enableModalScroll() {
    await browser.execute(() => {
      $('html').removeClass('disable-modal-scroll');
    });
  }

  async waitForTrackingRequest(expectedTrackingRequestCount = 1) {
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

      return trackedPageviews >= expectedTrackingRequestCount;
    });

    await browser.pause(500); // wait for matomo to process the tracking requests
  }

  async addStylesToPage(css: string) {
    await browser.execute(function (c) {
      document.head.insertAdjacentHTML('beforeend', `<style>${c}</style>`);
    } as any, css);
  }

  async waitForImages() {
    await browser.waitUntil(async () => {
      return browser.execute(function () {
        let isAllComplete = true;
        $('img').each((i, e) => {
          isAllComplete = isAllComplete && e.complete;
        });
        return isAllComplete;
      });
    }, { timeout: 20000 });
  }
}
