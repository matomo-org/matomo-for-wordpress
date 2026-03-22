/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Config from '../config.js';
import Visitor from '../visitor.js';
import BlogHomepagePage from '../../e2e/pageobjects/blog-homepage.page.js';
import BlogProductPage from '../../e2e/pageobjects/blog-product.page.js';

export default class ProductView extends Visitor {
  async visit() {
    await BlogHomepagePage.open();
    await this.waitForWpStatisticsTracking();

    const product = Config.products.next().value;
    await BlogProductPage.open(product);
    await this.waitForWpStatisticsTracking();
  }
}
