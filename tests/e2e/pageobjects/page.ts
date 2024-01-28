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
      overflow-y: visible !important;
      position: static !important;
    }

    html.disable-modal-scroll body > *:not(.modal) {
      display: none !important;
      visibility: hidden !important;
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

  // webdriverio can't seem to take screenshots of scrolling elements
  // properly. so for modals, we need to disable the modal styles and
  // take a picture of the full page, with everything but the modal hidden.
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

  // for wp themes/plugins that use react
  // see https://github.com/facebook/react/issues/10135#issuecomment-314441175 for details on method
  async setReactInputValue(selector, value) {
    await browser.execute((s, v) => {
      const element = window.jQuery(s)[0];
      const prototype = Object.getPrototypeOf(element);

      const valueSetter = Object.getOwnPropertyDescriptor(element, 'value')?.set;
      const prototypeValueSetter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;

      if (prototypeValueSetter && valueSetter !== prototypeValueSetter) {
        prototypeValueSetter.call(element, v);
      } else if (valueSetter) {
        valueSetter.call(element, v);
      } else {
        element.value = value;
      }

      element.dispatchEvent(new Event('input', { bubbles: true }));
    }, selector, value);
  }

  async prepareWpAdminForScreenshot() {
    await browser.execute(() => {
      window.jQuery('.notice-ocean-extra-plugin').hide();
      window.jQuery('.notice-ocean-extra-plugin .notice-dismiss').click();
      window.jQuery('#wpadminbar,#adminmenumain').hide();
    });
  }
}
