/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import BlogProductPage from './pageobjects/blog-product.page.js';
import BlogCheckoutPage from './pageobjects/blog-checkout.page.js';
import MatomoApi from './apiobjects/matomo.api.js';
import Website from './website.js';
import GlobalSetup from './global-setup.js';
import SettingsPage from './pageobjects/mwp-admin/settings.page.js';
import BlogHomepagePage from './pageobjects/blog-homepage.page.js';

describe('Tracking (Ecommerce)', function() {
  before(async () => {
    await GlobalSetup.setUp();
    await Website.deleteAllCookies();
  });

  it('should track ecommerce events and orders using the JS client', async () => {
    // TODO: these tests are not particularly great atm. there's no way to get the number of orders
    // overall or number of conversions overall without initiating archiving
    await Website.setUpWooCommerce();

    await BlogProductPage.open();
    await BlogProductPage.waitForTrackingRequest(1); // pageview + product view in one request

    await BlogProductPage.addToCart(); // tracked server side
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview refresh + product update

    await BlogProductPage.checkout(); // redirects to checkout
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    await BlogCheckoutPage.order(); // redirects to order received
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    await browser.pause(3000); // just to make sure everything gets tracked

    const visitsAfter = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: 'today',
      period: 'month',
      filter_limit: '100',
      format: 'json',
      test: '1',
    }));

    const visitsWithEcommerceOrder = visitsAfter.filter((v) => v.visitEcommerceStatus === 'ordered');

    expect(visitsWithEcommerceOrder.length).toEqual(1);
  });

  describe('cookieless', () => {
    async function enableCookielessTracking() {
      await SettingsPage.open();

      await browser.execute(() => {
        window.jQuery('#use_session_visitor_id').val(1);
        window.jQuery('#disable_cookies').val(1);
      });

      await SettingsPage.saveSettings();
    }

    async function disableCookielessTracking() {
      await SettingsPage.open();

      await browser.execute(() => {
        window.jQuery('#use_session_visitor_id').val(0);
        window.jQuery('#disable_cookies').val(0);
      });

      await SettingsPage.saveSettings();
    }

    let userAgent = '';
    before(async () => {
      userAgent = await browser.execute(() => navigator.userAgent);
      await enableCookielessTracking();
    });

    after(async () => {
      await browser.emulate('userAgent', userAgent);
      await disableCookielessTracking();
    });

    it('should track abandoned carts correctly with cookieless tracking and server side visitor ID', async () => {
      const countersBefore = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
        idSite: '1',
        lastMinutes: '60',
      }));

      // set new visitor
      const cookies = await browser.getCookies();
      for (let name in cookies) {
        console.log(`found cookie ${name}`);
        if (/^_pk_/.test(name)) {
          console.log(`deleting cookie ${name}`);
          await browser.deleteCookie(name);
        }
      }
      await browser.emulate('userAgent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.1958');

      await BlogHomepagePage.open();
      await BlogHomepagePage.waitForTrackingRequest(1); // pageview + product view in one request

      await BlogProductPage.open();
      await BlogProductPage.waitForTrackingRequest(1); // pageview + product view in one request

      await BlogProductPage.addToCart(); // tracked server side
      await BlogProductPage.waitForTrackingRequest(1); // pageview refresh + product update

      const counters = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
        idSite: '1',
        lastMinutes: '60',
      }));

      // note: the visitor log test will implicitly do more extensive test of the tracked data
      expect(counters).toHaveLength(1);
      // expect(counters[0].visits).toEqual(parseInt(countersBefore[0].visits, 10) + 1);
    });
  });
});
