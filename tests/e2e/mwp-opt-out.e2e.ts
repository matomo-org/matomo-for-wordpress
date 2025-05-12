/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import Website from './website.js';
import ContactUsPage from './pageobjects/contact-us.page.js';

describe('OptOut', () => {
    const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

    before(async () => {
        if (!process.env.PHP_VERSION) { // TODO: check this in wdio.conf
            throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
        }

        await Website.login();
    });

    it('should display the Matomo Opt-Out form correctly when the Opt-Out shortcode is used', async () => {
        await ContactUsPage.open();

        await ContactUsPage.prepareBlogPostPageForScreenshot();
        await expect(
            await browser.checkFullPageScreen(`mwp.page-with-opt-out.${process.env.PHP_VERSION}${trunkSuffix}`)
        ).toEqual(0);
    });
});
