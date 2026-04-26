/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
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
    await $('.single_add_to_cart_button').waitForExist();
    await browser.execute(() => {
      window.jQuery('.single_add_to_cart_button:visible')[0].click();
    });

    await browser.waitUntil(async () => {
      return browser.execute(() => {
        return window.jQuery && window.jQuery('a:contains("View cart"):visible').length > 0;
      });
    });

    await Website.retry(3, async () => {
      let cp = await browser.execute(() => {
        return window.jQuery ? window.jQuery('a:contains("View cart"):visible').attr('href') : null;
      });

      if (!cp) {
        throw new Error('force retry');
      }

      return cp;
    }, 500);

    await browser.execute(() => {
      window.jQuery('a:contains("View cart"):visible')[0].click();
    });

    console.log('checking');
    console.log((new Error()).stack);
    await browser.waitUntil(async () => {
      console.log('cookies');
      console.log((await browser.getCookies()).map(c => c.name));
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
    await browser.waitUntil(() => {
      return browser.execute(() => window.jQuery('.checkout-button,.wc-block-cart__submit-button').length > 0);
    }, { timeout: 60000 });

    await browser.execute(() => {
      window.jQuery('.checkout-button,.wc-block-cart__submit-button')[0].click();
    });

    browser.waitUntil(() => {
      return browser.execute(() => window.jQuery('input#email,#billing_email').length > 0);
    }, { timeout: 60000 });
  }
}

export default new BlogProductPage();
