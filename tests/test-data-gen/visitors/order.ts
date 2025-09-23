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
import Config from '../config.js';
import BlogCheckoutPage from '../../e2e/pageobjects/blog-checkout.page.js';

export default class Order extends Visitor {
  async visit() {
    await BlogHomepagePage.open();
    await this.waitForWpStatisticsTracking();

    const product1 = Config.products.next().value;
    await BlogProductPage.open(product1);
    await this.waitForWpStatisticsTracking();
    await BlogProductPage.addToCart();

    const product2 = Config.products.next().value;
    await BlogProductPage.open(product2);
    await this.waitForWpStatisticsTracking();

    const product3 = Config.products.next().value;
    await BlogProductPage.open(product3);
    await this.waitForWpStatisticsTracking();
    await BlogProductPage.addToCart();
    await BlogProductPage.open(product3);
    await this.waitForWpStatisticsTracking();
    await BlogProductPage.addToCart();

    await BlogProductPage.checkout();
    await this.waitForWpStatisticsTracking();

    await BlogCheckoutPage.order();
    await this.waitForWpStatisticsTracking();
  }
}
