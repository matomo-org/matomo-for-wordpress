/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class BlogProductPage extends Page {
  open() {
    return super.open('/?product=folding-monitors');
  }

  async addToCart() {
    await $('button[name="add-to-cart"]').waitForExist();
    await browser.execute(() => {
      window.jQuery('button[name="add-to-cart"]').click();
    });
    await browser.waitUntil(async () => {
      const exists = await browser.execute(() => {
        return window.jQuery('a:contains("View cart")').length > 0;
      });
      return exists;
    });
    await browser.pause(500);
    await browser.execute(() => {
      window.jQuery('a:contains("View cart")')[0].click();
    });
    await $('.wc-block-cart__submit-button').waitForExist();
  }

  async checkout() {
    await $('.wc-block-cart__submit-button').click();
  }
}

export default new BlogProductPage();
