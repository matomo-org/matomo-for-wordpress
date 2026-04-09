/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
import * as path from 'node:path';
import * as fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import interceptor from 'wdio-intercept-service/lib/interceptor.js';
import Website from '../website.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default class Page {

  public static ipAddressOverride: string|null = null;
  public static userAgentOverride: string|null = null;
  public static referrerOverride: string|null = null;

  public static interceptorSetup = false;

  async setupInterceptor() {
    if (Page.interceptorSetup) {
      return;
    }

    const interceptorSetup = interceptor.setup
      .toString()
      // for some reason, the script fails to execute as a preload script
      // if \r or \n are in the code. so we use ordinal values to workaround this.
      .replace("'\\r\\n'", 'String.fromCharCode(13) + String.fromCharCode(10)')
      .replace('function setup(done) {', '')
      .replace(/}\s*$/, '')
      .replace('done(window[NAMESPACE]);', '');

    // use init script instead of setupInterceptor() so ajax requests sent
    // on page initialization are captured.
    await browser.addInitScript(function (s) {
      return (new Function(s))();
    }, interceptorSetup);

    Page.interceptorSetup = true;
  }

  async setupUncaughtExceptionHandler() {
    await browser.addInitScript(function () {
      addEventListener('unhandledrejection', (event) => {
        const reasonText = event.reason ? (event.reason.stack || event.reason.message || JSON.stringify(event.reason)) : 'null';
        console.log(`uncaught exception: ${reasonText}`);
      })
    });
  }

  async open(path: string) {
    await this.setupInterceptor();
    await this.setupUncaughtExceptionHandler();

    const baseUrl = await Website.baseUrl();

    if (!/^\//.test(path)) {
      path = `/${path}`;
    }

    this.overrideRequestDetails(Page.ipAddressOverride, Page.userAgentOverride, Page.referrerOverride);

    let result;
    result = await Website.retry(3, async () => {
      let r = await browser.url(`${baseUrl}${path}`);
      if (await $('#user_login').isExisting()) {
        await Website.login(); // logged out for some reason
        throw new Error('force retry');
      }

      // sometimes files fail to include on github actions resulting in a random
      // fatal error
      const hasCriticalError = await browser.execute(
        () => document.documentElement.innerHTML
          .includes('There has been a critical error on this website')
      );
      if (hasCriticalError) {
        throw new Error('force retry');
      }

      return r;
    });

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
      $('html').removeClass('disable-hover');
    });
  }

  async disableHoverStyles() {
    await browser.execute(() => {
      $('html').addClass('disable-hover');
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
    }, { timeout: 20000 });

    await browser.pause(500); // wait for matomo to process the tracking requests
  }

  async addStylesToPage(css: string) {
    await browser.execute(function (c) {
      document.head.insertAdjacentHTML('beforeend', `<style>${c}</style>`);
    } as any, css);
    await browser.pause(500); // wait for the browser to finish rendering
  }

  async waitForImages() {
    try {
      await browser.waitUntil(async () => {
        return browser.execute(function () {
          let isAllComplete = true;
          $('img').each((i, e) => {
            isAllComplete = isAllComplete && e.complete;
          });
          return isAllComplete;
        });
      }, { timeout: 60000 });
    } catch (e) {
      // ignore and try to compare a screenshot anyway
    }
  }

  // for wp themes/plugins that use react
  // see https://github.com/facebook/react/issues/10135#issuecomment-314441175 for details on method
  async setReactInputValue(selector, value) {
    await browser.execute((s, v) => {
      const element = window.jQuery(s)[0];
      if (!element) {
        return;
      }

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

      element.dispatchEvent(new Event('input', {bubbles: true}));
    }, selector, value);
  }

  async prepareWpAdminForScreenshot() {
    await browser.execute(() => {
      if (!window.jQuery('#wpadminbar,#adminmenumain').length) {
        throw new Error('cannot find elements to hide');
      }

      window.jQuery('.notice:contains(An error occurred while updating the geolocation database)').hide();
      window.jQuery('.notice-ocean-extra-plugin').hide();
      window.jQuery('.notice-ocean-extra-plugin .notice-dismiss').click();
      window.jQuery('#wpadminbar,#adminmenumain').hide();
      window.jQuery('#footer-upgrade').hide();
    });

    await browser.waitUntil(async () => {
      return await browser.execute(() => {
        return !window.jQuery('#wpadminbar').is(':visible')
          && !window.jQuery('#adminmenumain').is(':visible');
      });
    });

    await browser.pause(250);
  }

  async prepareBlogPostPageForScreenshot() {
    await browser.execute(() => {
      window.jQuery('#wpadminbar').hide();
    });
  }

  async undoChangesToWpAdminForScreenshot() {
    await browser.execute(() => {
      window.jQuery('.notice-ocean-extra-plugin').show();
      window.jQuery('#wpadminbar,#adminmenumain').show();
      window.jQuery('#footer-upgrade').show();
    });
  }

  overrideRequestDetails(ipAddress: string, userAgent: string, referrer: string) {
    const overrides = {
      ipAddress,
      userAgent,
      referrer,
    };

    const overrideFile = path.join(__dirname, '..', '..', '..', '.e2e-test-overrides.json');
    fs.writeFileSync(overrideFile, JSON.stringify(overrides));
  }

  /**
   * webdriverio's checkElement method (in the image comparison service) does not work.
   * so if we want to take a screenshot of a specific element, it can't be used. this
   * function is a workaround that will hide everything but the elements referenced by
   * the selector.
   *
   * @param selector
   */
  async hideAllButElement( selector: string ) {
    await browser.execute((s) => {
      (function () {
        function visitNode(n: HTMLElement) {
          const isSelectedNode = window.jQuery(n).is(s);
          if (isSelectedNode) {
            return; // if node is one we want to screenshot, do nothing
          }

          // if node contains node we want to screenshot, recurse
          if (window.jQuery(n).find(s).length > 0) {
            for (let i = 0; i < n.children.length; ++i) {
              const child = n.children.item(i);
              if (child instanceof HTMLElement) {
                visitNode(child);
              }
            }
            return;
          }

          // if node does not contain node we want to screenshot, hide
          window.jQuery(n).hide();
        }

        visitNode(document.documentElement);
      })();
    }, selector);
  }
}
