/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import AnonymizeDataPage from './pageobjects/matomo-admin/privacy/anonymize-data.page.js';
import UsersOptOutPage from './pageobjects/matomo-admin/privacy/users-opt-out.page.js';
import AskingForConsentPage from './pageobjects/matomo-admin/privacy/asking-for-consent.page.js';
import GdprOverviewPage from './pageobjects/matomo-admin/privacy/gdpr-overview.page.js';
import GdprToolsPage from './pageobjects/matomo-admin/privacy/gdpr-tools.page.js';
import Website from './website.js';

describe('Matomo Admin > Privacy', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the anonymize data page correctly', async () => {
    await AnonymizeDataPage.open();

    await $('#anonymizeStartDate').waitForExist();

    // hide the date selectors
    await browser.execute(function () {
      $('#anonymizeStartDate').hide();
      $('#anonymizeEndDate').hide();
    });

    await AnonymizeDataPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-admin.privacy.anonymize-data.${process.env.PHP_VERSION}`)
    ).toEqual(0)
  });

  it('should load the users opt-out page correctly', async () => {
    await UsersOptOutPage.open();

    await UsersOptOutPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-admin.privacy.users-opt-out.${process.env.PHP_VERSION}`)
    ).toEqual(0)
  });

  it('should load the asking for consent page correctly', async () => {
    await AskingForConsentPage.open();

    await AskingForConsentPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-admin.privacy.asking-for-consent.${process.env.PHP_VERSION}`)
    ).toEqual(0)
  });

  it('should load the gdpr overview page correctly', async () => {
    await GdprOverviewPage.open();

    await GdprOverviewPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-admin.privacy.gdpr-overview.${process.env.PHP_VERSION}`)
    ).toEqual(0);
  });

  it('should load the gdpr tools page correctly', async () => {
    await GdprToolsPage.open();

    await GdprToolsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-admin.privacy.gdpr-tools.${process.env.PHP_VERSION}`)
    ).toBeLessThan(0.015);
  });
});
