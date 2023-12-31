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

    await $('#anonymizeStartDate').waitForExist({ timeout: 2000 });

    // wait for controls to be initialized
    await browser.waitUntil(async () => {
      function sub(value: number) {
        return value < 10 ? `0${value}` : value;
      }

      const date = await browser.execute(function () {
        return $('#anonymizeStartDate').val();
      });

      const now = new Date();
      return date === `${now.getFullYear()}-${sub(now.getMonth() + 1)}-${sub(now.getDay() + 1)}`;
    });

    // change the date
    await browser.execute(function () {
      $('#anonymizeStartDate').val('2023-12-05');
      $('#anonymizeEndDate').val('2023-12-05');
    });

    await expect(
      await browser.checkFullPageScreen('matomo-admin.privacy.anonymize-data')
    ).toEqual(0)
  });

  it('should load the users opt-out page correctly', async () => {
    await UsersOptOutPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.privacy.users-opt-out')
    ).toEqual(0)
  });

  it('should load the asking for consent page correctly', async () => {
    await AskingForConsentPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.privacy.asking-for-consent')
    ).toEqual(0)
  });

  it('should load the gdpr overview page correctly', async () => {
    await GdprOverviewPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.privacy.gdpr-overview')
    ).toEqual(0);
  });

  it('should load the gdpr tools page correctly', async () => {
    await GdprToolsPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.privacy.gdpr-tools')
    ).toEqual(0);
  });
});
