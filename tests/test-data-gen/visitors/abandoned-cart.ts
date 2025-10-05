/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Visitor from '../visitor.js';
import BlogHomepagePage from '../../e2e/pageobjects/blog-homepage.page.js';
import BlogProductPage from '../../e2e/pageobjects/blog-product.page.js';
import BlogAllProductsPage from '../../e2e/pageobjects/blog-all-products.page.js';
import Config from '../config.js';

export default class AbandonedCart extends Visitor {
  async visit() {
    await BlogHomepagePage.open();
    await this.waitForWpStatisticsTracking();

    await BlogAllProductsPage.open();
    await this.waitForWpStatisticsTracking();

    const firstProduct = Config.products.next().value;

    await BlogProductPage.open(firstProduct);
    await BlogProductPage.addToCart();
    await this.waitForWpStatisticsTracking();

    const secondProduct = Config.products.next().value;
    await BlogProductPage.searchProducts(secondProduct);
    await this.waitForWpStatisticsTracking();

    await BlogProductPage.open(secondProduct);
    await this.waitForWpStatisticsTracking();
    await BlogProductPage.addToCart();
  }
}
