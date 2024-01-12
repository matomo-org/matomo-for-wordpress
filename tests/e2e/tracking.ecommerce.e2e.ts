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

describe('Tracking (Ecommerce)', () => {
  before(async () => {
    await Website.deleteAllCookies();
    await Website.setUpWooCommerce();
  });

  it('should track ecommerce events and orders using the JS client', async () => {
    const countersBefore = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(countersBefore).toHaveLength(1);

    await BlogProductPage.open();
    await BlogProductPage.waitForTrackingRequest(1); // pageview + product view in one request

    await BlogProductPage.addToCart(); // tracked server side
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview refresh + product update

    await BlogProductPage.checkout(); // redirects to checkout
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    await BlogCheckoutPage.order(); // redirects to order received
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    const countersAfter = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(countersAfter).toEqual([{
      visits: `${parseInt(countersBefore[0].visits, 10) + 1}`,
      actions: `${parseInt(countersBefore[0].actions, 10) + 4}`,
      visitors: `${parseInt(countersBefore[0].visitors, 10) + 1}`,
      visitsConverted: `${parseInt(countersBefore[0].visitors, 0) + 2}`,
    }]);
  });
});
