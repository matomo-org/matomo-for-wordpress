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
        await Website.login();
    });

    it('should display the Matomo Opt-Out form correctly when the Opt-Out shortcode is used', async () => {
        await ContactUsPage.open();

        await ContactUsPage.prepareBlogPostPageForScreenshot();
        await expect(
            await browser.checkFullPageScreen(`mwp.page-with-opt-out.${process.env.PHP_VERSION}${trunkSuffix}`)
        ).toEqual(0);
    });

    it('should opt the user out when the checkbox is unchecked (classic)', async () => {
        await ContactUsPage.clickOptOutClassic();
        await ContactUsPage.open();

        await ContactUsPage.prepareBlogPostPageForScreenshot();
        await expect(
            await browser.checkFullPageScreen(`mwp.page-with-opt-out.classic-changed.${process.env.PHP_VERSION}${trunkSuffix}`)
        ).toEqual(0);
    });

    it('should opt the user in when the checkbox is checked (classic)', async () => {
        await ContactUsPage.clickOptOutClassic();
        await ContactUsPage.open();

        const countOfCheckedInputs = await browser.execute(() => {
            return window.jQuery('input:checked').length;
        });

        expect(countOfCheckedInputs).toEqual(2);
    });

    it('should opt the user out when the checkbox is unchecked (new)', async () => {
        await ContactUsPage.clickOptOut();
        await ContactUsPage.open();

        const countOfCheckedInputs = await browser.execute(() => {
            return window.jQuery('input:checked').length;
        });

        expect(countOfCheckedInputs).toEqual(0);
    });

    it('should opt the user in when the checkbox is checked (new)', async () => {
        await ContactUsPage.clickOptOut();
        await ContactUsPage.open();

        const countOfCheckedInputs = await browser.execute(() => {
            return window.jQuery('input:checked').length;
        });

        expect(countOfCheckedInputs).toEqual(2);
    });
});
