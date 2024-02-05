/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import GeneralSettingsPage from './pageobjects/matomo-admin/system/general-settings.page.js';
import MobileMessagingPage from './pageobjects/matomo-admin/system/mobile-messaging.page.js';
import Website from './website.js';

describe('Matomo Admin > System', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the general settings page correctly', async () => {
    await GeneralSettingsPage.open();

    await GeneralSettingsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-admin.system.general-settings')
    ).toEqual(0);
  });

  it('should load the mobile messaging page correctly', async () => {
    await MobileMessagingPage.open();

    await MobileMessagingPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-admin.system.mobile-messaging')
    ).toEqual(0);
  });
});
