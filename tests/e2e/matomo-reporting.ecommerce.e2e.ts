/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import EcommerceLogPage from './pageobjects/matomo-reporting/ecommerce/ecommerce-log.page.js';
import OverviewPage from './pageobjects/matomo-reporting/ecommerce/overview.page.js';
import ProductsPage from './pageobjects/matomo-reporting/ecommerce/products.page.js';
import SalesPage from './pageobjects/matomo-reporting/ecommerce/sales.page.js';
import Website from './website.js';

describe('Matomo Reporting > Ecommerce', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the overview page correctly', async () => {
    await OverviewPage.open();

    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ecommerce.overview')
    ).toBeLessThan(0.1);
  });

  it('should load the ecommerce log page correctly', async () => {
    await EcommerceLogPage.open();

    await EcommerceLogPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ecommerce.ecommerce-log')
    ).toBeLessThan(0.1);
  });

  it('should load the products page correctly', async () => {
    await ProductsPage.open();

    await ProductsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ecommerce.products')
    ).toBeLessThan(0.1);
  });

  it('should load the sales page correctly', async () => {
    await SalesPage.open();

    await SalesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ecommerce.sales')
    ).toBeLessThan(0.1);
  });
});
