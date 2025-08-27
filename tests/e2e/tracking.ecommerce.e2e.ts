/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import fetch from 'node-fetch';
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

  async function checkPageHasForcedVisitorId() {
    const headHtml = await browser.execute(() => document.head.innerHTML);
    expect(headHtml).toContain('window._paq.push(["setVisitorId"');
  }

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

      const checked = await browser.execute(() => window.jQuery('#use_session_visitor_id').is(':checked'));
      expect(checked).toBeFalsy();
    }

    before(async () => {
      const newUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 Edge/18.1958';
      await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'matomo_test_set_custom_user_agent',
          ua: newUserAgent,
        }),
      });

      await enableCookielessTracking();

      await SettingsPage.expandAllTrackingSettingsSections();
    });

    after(async () => {
      await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'matomo_test_set_custom_user_agent',
        }),
      });

      await disableCookielessTracking();
    });

    it('should track abandoned carts correctly with cookieless tracking and server side visitor ID', async () => {
      const countersBefore = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
        idSite: '1',
        lastMinutes: '60',
      }));

      // set new visitor
      let cookies = await browser.getCookies();
      for (let name in cookies) {
        if (/^_pk_/.test(name)) {
          await browser.deleteCookie(name);
        }
      }

      await BlogHomepagePage.open();
      await BlogHomepagePage.waitForTrackingRequest(1); // pageview + product view in one request
      await checkPageHasForcedVisitorId();

      await BlogProductPage.open();
      await BlogProductPage.waitForTrackingRequest(1); // pageview + product view in one request
      await checkPageHasForcedVisitorId();

      await BlogProductPage.addToCart(); // tracked server side
      await BlogProductPage.waitForTrackingRequest(1); // pageview refresh + product update
      await checkPageHasForcedVisitorId();

      await browser.pause(1000);

      // ensure we are doing cookieless tracking
      const matomoCookies = Object.keys(await browser.getCookies()).filter(k => /^_pk_/.test(k));
      expect(matomoCookies).toEqual([]);

      // check that a new visit was tracked, instead of tracking to an existing visit
      const counters = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
        idSite: '1',
        lastMinutes: '60',
      }));

      expect(counters).toHaveLength(1);
      expect(parseInt(counters[0].visits, 10)).toEqual(parseInt(countersBefore[0].visits, 10) + 1);

      // check latest visit in visitor log has an abandoned cart event + other actions
      const visits = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
        idSite: '1',
        period: 'day',
        date: 'today',
      }));

      expect(visits.length).toBeGreaterThan(1);
      expect(parseInt(visits[0].totalAbandonedCartsRevenue, 10)).toBeGreaterThan(0);
      expect(parseInt(visits[0].totalAbandonedCarts, 10)).toEqual(1);
      expect(parseInt(visits[0].totalAbandonedCartsItems, 10)).toEqual(1);

      const firstVisitPageviews = visits[0].actionDetails.filter(a => a.type === 'action');
      expect(firstVisitPageviews.length).toBeGreaterThan(0);

      // check that there are no visits with only ecommerce actions
      const onlyEcommerceVisits = visits.filter((v) => {
        return (v.actionDetails || []).every((a) => /^ecommerce/.test(a));
      });
      expect(onlyEcommerceVisits.length).toEqual(0);
    });
  });
});
