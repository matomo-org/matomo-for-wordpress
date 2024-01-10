/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import BlogProductPage from './pageobjects/blog-product.page.js';
import MatomoApi from './apiobjects/matomo.api.js';

describe('Tracking (Ecommerce)', () => {
  it('should track ecommerce events and orders using the JS client', async () => {
    // TODO
    await BlogProductPage.open();

    await BlogProductPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-tracking.ecommerce.product')
    ).toBeLessThan(0.1);
  });
});
