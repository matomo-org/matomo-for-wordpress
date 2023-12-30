/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import EmailReportsPage from './pageobjects/matomo-admin/personal/email-reports.page.js';
import PersonalSettingsPage from './pageobjects/matomo-admin/personal/settings.page.js';
import Website from './website.js';

describe('Matomo Admin > Personal', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the personal settings page correctly', async () => {
    await PersonalSettingsPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.personal.settings')
    ).toEqual(0);
  });

  it('should load the email reports page correctly', async () => {
    await EmailReportsPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.personal.email-reports')
    ).toEqual(0);
  });

  it('should create an email report successfully', async () => {
    await EmailReportsPage.startAddReport();
    await EmailReportsPage.createNewReport();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.personal.email-reports.create')
    ).toEqual(0);
  });

  it('should load the created email report successfully', async () => {
    await EmailReportsPage.downloadReport(1);

    try {
      await expect(
        await browser.checkFullPageScreen('matomo-admin.personal.email-reports.download')
      ).toEqual(0);
    } finally {
      await browser.closeWindow(); // downloading an html report should create a new tab
    }

    // TODO: would be good to test sending an email as well, to ensure it works with wordpress
  });
});
