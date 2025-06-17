/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';
import SearchPerformancePage from './pageobjects/matomo-plugins/searchenginekeywordsperformance/searchperformance.page.js';

describe('Matomo Plugins > SearchEngineKeywordsPerformance', () => {
    before(async () => {
        await Website.login();
    });

    it('should display the plugin admin settings page correctly', async () => {
        await SearchPerformancePage.open();

        await SearchPerformancePage.prepareMatomoPageForScreenshot();
        await expect(
            await browser.checkFullPageScreen('matomo.searchenginekeywordsperformance.admin')
        ).toEqual(0);
    });
});
