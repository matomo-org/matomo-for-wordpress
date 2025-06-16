/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Website from './website.js';
import SummaryPage from './pageobjects/mwp-admin/summary.page.js';
import OverviewPage from './pageobjects/matomo-reporting/visitors/overview.page';

describe('MWP Roles', () => {
    before(async () => {
        await Website.logout();
    });

    after(async () => {
        await Website.login();
    });

    it('should display the wordpress admin dashboard when the user has view access', async () => {
        await Website.login('matomoviewuser', 'matomoviewuser');

        await $('form#your-profile').waitForExist({ timeout: 60000 });

        const menuItems = await browser.execute(() => {
            return window
                .jQuery('.wp-menu-name')
                .toArray()
                .map((e) => window.jQuery(e).text().trim());
        });

        expect(menuItems).toContain('Dashboard');
        expect(menuItems).toContain('Matomo Analytics');
    });

    it('should display MWP pages when the user has view access', async () => {
        await SummaryPage.open();

        await $('#dashboard-widgets canvas').waitForExist({ timeout: 60000 });

        const matomoMenuItems = await browser.execute(() => {
            return window
                .jQuery('.wp-submenu a')
                .toArray()
                .map((e) => window.jQuery(e).text().trim());
        });

        expect(matomoMenuItems).toEqual(['Summary', 'Reporting', 'Help', 'Marketplace']);
    });

    it('should display the Matomo backend correctly when the user has view access', async () => {
        await OverviewPage.open();
        await $('.dataTableVizEvolution').waitForExist({ timeout: 60000 });
    });
});
