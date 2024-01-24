/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class BlogCheckoutPage extends Page {
  async order() {
    await browser.waitUntil(() => {
      return browser.execute(() => window.jQuery('input#email,#billing_email').length);
    });
    await browser.pause(500); // just in case

    await browser.execute(() => {
      window.jQuery('input#email,#billing_email')[0].value = 'testemail@example.com';
      window.jQuery('input#billing-first_name,#billing_first_name')[0].value = 'FirstName';
      window.jQuery('input#billing-last_name,#billing_last_name')[0].value = 'McLastNamington';
      window.jQuery('input#billing-address_1,#billing_address_1')[0].value = '200 Santa Monica Pier';
      window.jQuery('input#billing-city,#billing_city')[0].value = 'Santa Monica';
      window.jQuery('#billing-state input').val('California'); // local
      window.jQuery('#billing_state').val('CA'); // CI
      window.jQuery('input#billing-postcode,#billing_postcode')[0].value = '90401';
      try {
        window.jQuery('#billing_phone').val('555-123-4567');
      } catch (e) {
        // ignore
      }
    });
    await browser.pause(1500);

    await browser.execute(() => {
      window.jQuery('.wc-block-components-checkout-place-order-button,#place_order')[0].click();
    });

    await $('li.woocommerce-order-overview__order').waitForDisplayed({ timeout: 20000 });
  }
}

export default new BlogCheckoutPage();
