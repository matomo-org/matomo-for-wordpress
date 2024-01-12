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
    console.log(await browser.execute(() => document.body.innerHTML));
    await $('input#email').setValue('testemail@example.com');
    await $('input#billing-first_name').setValue('FirstName');
    await $('input#billing-last_name').setValue('McLastNamington');
    await $('input#billing-address_1').setValue('200 Santa Monica Pier');
    await $('input#billing-city').setValue('Santa Monica');
    await browser.execute(() => {
      window.jQuery('#billing-state input').val('California');
    });
    await $('input#billing-postcode').setValue('90401');
    await browser.pause(1500);

    await browser.execute(() => {
      window.jQuery('.wc-block-components-checkout-place-order-button')[0].click();
    });

    await $('li.woocommerce-order-overview__order').waitForDisplayed();
  }
}

export default new BlogCheckoutPage();
