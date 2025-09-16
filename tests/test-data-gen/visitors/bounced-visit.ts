/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Visitor from '../visitor.js';
import BlogHomepagePage from '../../e2e/pageobjects/blog-homepage.page.js';

export default class BouncedVisit extends Visitor {
  async visit() {
    await BlogHomepagePage.open();
    await this.waitForWpStatisticsTracking();
  }
}
