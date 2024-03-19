/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';
import SummaryPage from './pageobjects/mwp-admin/summary.page';
import OverviewPage from "./pageobjects/matomo-reporting/visitors/overview.page";

describe('MWP Language', () => {
  before(async () => {
    await Website.login();
  });

  it('should use the appropriate language in MWP admin when the site language changes', async () => {
    await Website.setSiteLanguage('de');

    await SummaryPage.open();
    await SummaryPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.site-lang.mwp-admin')
    ).toEqual(0);
  });

  it('should use the appropriate language in Matomo Reporting when the site language changes', async () => {
    await OverviewPage.open();
    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.site-lang.matomo-reporting')
    ).toEqual(0);
  });

  it('should use the appropriate language in MWP admin when the user profile language changes', async () => {
    await Website.setUserProfileLanguage('fr');

    await SummaryPage.open();
    await SummaryPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.profile-lang.mwp-admin')
    ).toEqual(0);
  });

  it('should use the appropriate language in Matomo Reporting when the user profile language changes', async () => {
    await OverviewPage.open();
    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.profile-lang.matomo-reporting')
    ).toEqual(0);
  });

  it('should use the appropriate language in MWP admin when a plugin calls switch_to_locale', async () => {
    // mwp_switch_to_locale query param handled by test-utility-plugin.php
    await SummaryPage.openWith({ mwp_switch_to_locale: 'jp' });
    await SummaryPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.switch-to-locale.mwp-admin')
    ).toEqual(0);
  });

  it('should use the appropriate language in Matomo Reporting when a plugin calls switch_to_locale', async () => {
    // mwp_switch_to_locale query param handled by test-utility-plugin.php
    await OverviewPage.openWith({ mwp_switch_to_locale: 'jp' });
    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-lang.switch-to-locale.matomo-reporting')
    ).toEqual(0);
  });
});
