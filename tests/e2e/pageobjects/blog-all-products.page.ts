/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import Page from './page.js';

class BlogAllProductsPage extends Page {
  async open() {
    await super.open('/shop');
    await $('.wp-block-search__input').waitForExist();
  }
}

export default new BlogAllProductsPage();
