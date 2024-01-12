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

    const checkoutPage = await browser.execute(() => {
      return window.jQuery('a:contains("View cart")').attr('href');
    });
    await browser.url(checkoutPage);

    await browser.waitUntil(() => {
      return browser.execute(() => {
        // the checkout button can have different classes when run locally vs. CI
        return window.jQuery('.checkout-button,.wc-block-cart__submit-button').length > 0;
      });
    });
  }

  async checkout() {
    await browser.execute(() => {
      window.jQuery('.checkout-button,.wc-block-cart__submit-button')[0].click();
    });
  }
}

export default new BlogProductPage();
