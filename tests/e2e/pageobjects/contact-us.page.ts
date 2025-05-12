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
        await $('#matomo-opt-out-form-embed #trackVisits').waitForExist({ timeout: 60000 });

        return result;
    }
}

export default new ContactUsPage();
