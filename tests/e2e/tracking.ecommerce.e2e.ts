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
  });

  it('should track ecommerce events and orders using the JS client', async () => {
    // TODO: these tests are not particularly great atm. there's no way to get the number of orders
    // overall or number of conversions overall without
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
    console.log(visitsAfter.map((v) => v.visitEcommerceStatus));

    expect(visitsWithEcommerceOrder.length).toEqual(1);
  });
});
