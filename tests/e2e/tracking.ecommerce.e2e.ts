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

describe('Tracking (Ecommerce)', () => {
  it('should track ecommerce events and orders using the JS client', async () => {
    await BlogProductPage.open();
    await BlogProductPage.waitForTrackingRequest(1); // pageview + product view in one request

    await BlogProductPage.addToCart(); // tracked server side
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview refresh + product update

    await BlogProductPage.checkout(); // redirects to checkout
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    await BlogCheckoutPage.order(); // redirects to order received
    await BlogCheckoutPage.waitForTrackingRequest(1); // pageview

    // TODO: check tracked
  });
});
