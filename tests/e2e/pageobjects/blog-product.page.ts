/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Website from '../website.js';
import Page from './page.js';

export enum Product {
  CAMERA_TRIPOD_SMALL = 'camera-tripod-small',
  SPOTLIGHT = 'spotlight',
  FOLDING_MONITORS = 'folding-monitors',
  FILM_PROJECTOR_LENS = 'film-projector-lens',
  CEILING_FAN = 'ceiling-fan-pink',
}

class BlogProductPage extends Page {

  open(productSlug = Product.FOLDING_MONITORS) {
    return super.open(`/product/${productSlug}/`);
  }

  async addToCart() {
    await $('button[name="add-to-cart"]').waitForExist();
    await browser.execute(() => {
      window.jQuery('button[name="add-to-cart"]').click();
    });
    await browser.waitUntil(async () => {
      const exists = await browser.execute(() => {
        return window.jQuery && window.jQuery('a:contains("View cart")').length > 0;
      });
      return exists;
    });

    let checkoutPage = await Website.retry(3, async () => {
      let cp = await browser.execute(() => {
        return window.jQuery ? window.jQuery('a:contains("View cart")').attr('href') : null;
      });

      if (!cp) {
        throw new Error('force retry');
      }

      return cp;
    }, 500);
    await browser.url(checkoutPage);

    await browser.waitUntil(() => {
      return browser.execute(() => {
        // the checkout button can have different classes when run locally vs. CI
        return window.jQuery('.checkout-button,.wc-block-cart__submit-button').length > 0;
      });
    }, { timeout: 60000 });
  }

  async searchProducts(searchText: string) {
    await browser.execute((t) => {
      window.jQuery('form .wp-block-search__input').val(t);
      window.jQuery('form .wp-block-search__button')[0].click();
    }, searchText);

    await browser.waitUntil(() => {
      return browser.execute(() => {
        return window.jQuery('h1:contains(Search Results Found)').length > 0;
      });
    });
  }

  async checkout() {
    await Website.retry(3, async () => {
      await browser.execute(() => {
        window.jQuery('.checkout-button,.wc-block-cart__submit-button')[0].click();
      });

      return $('input#email,#billing_email').waitForExist({ timeout: 60000 });
    });
  }
}

export default new BlogProductPage();
