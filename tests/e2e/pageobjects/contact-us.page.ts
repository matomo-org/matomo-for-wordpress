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

        await $('#matomo-opt-out-form-embed').waitForExist({ timeout: 30000 });
        await $('#matomo-opt-out-form-embed #trackVisits').waitForDisplayed({ timeout: 60000 });

        return result;
    }

    async clickOptOut() {
        await $('#matomo-opt-out-form-embed #trackVisits').click();
        await browser.pause(500);
    }

    async clickOptOutClassic() {
        await $('form #matomo_optout_checkbox').click();
        await browser.pause(500);
    }
}

export default new ContactUsPage();
