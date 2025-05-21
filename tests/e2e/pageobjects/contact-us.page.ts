/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class ContactUsPage extends Page {
    async open() {
        const result = super.open('/contact-us/');

        await $('#matomo_optout_checkbox').waitForExist({ timeout: 30000 });
        await $('#matomo-opt-out-form-embed').waitForExist({ timeout: 30000 });
        await browser.waitUntil(() => browser.execute(() => {
            return window.jQuery('input#trackVisits:visible').length > 0;
        }), { timeout: 60000 })

        await browser.pause(10000);

        return result;
    }

    async clickOptOut() {
        await browser.execute(() => {
            window.jQuery('#trackVisits').click();
        });
        await browser.pause(1000);
    }

    async clickOptOutClassic() {
        await browser.execute(() => {
            window.jQuery('form #matomo_optout_checkbox').click();
        });
        await browser.pause(1000);
    }
}

export default new ContactUsPage();
