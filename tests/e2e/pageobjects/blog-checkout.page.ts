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
    await $('input#email,#billing_email').waitForExist();

    await this.setReactInputValue('input#email,#billing_email', 'testemail@example.com');
    await this.setReactInputValue('input#billing-first_name,#billing_first_name', 'FirstName');
    await this.setReactInputValue('input#billing-last_name,#billing_last_name', 'McLastNamington');
    await this.setReactInputValue('input#billing-address_1,#billing_address_1', '200 Santa Monica Pier');
    await this.setReactInputValue('input#billing-city,#billing_city', 'Santa Monica');

    await browser.execute(() => {
      window.jQuery('#billing-state input').val('California'); // local
      window.jQuery('#billing_state').val('CA'); // CI
    });

    await this.setReactInputValue('input#billing-postcode,#billing_postcode', '90401');
    try {
      await this.setReactInputValue('#billing_phone', '555-123-4567');
    } catch (e) {
      // ignore
    }

    await browser.pause(1500);

    const numAttempts = 3;
    for (let i = 0; i < numAttempts; ++i) {
      await browser.execute(() => {
        window.jQuery('.wc-block-components-checkout-place-order-button,#place_order')[0].click();
      });

      try {
        await $('li.woocommerce-order-overview__order').waitForDisplayed({timeout: 20000});
        break;
      } catch (e) {
        if (i + 1 >= numAttempts) {
          throw e;
        }
      }
    }
  }
}

export default new BlogCheckoutPage();
