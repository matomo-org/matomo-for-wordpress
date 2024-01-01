/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import TrackingFailuresPage from './pageobjects/matomo-admin/diagnostic/tracking-failures.page.js';
import DeviceDetectionPage from './pageobjects/matomo-admin/diagnostic/device-detection.page.js';
import Website from './website.js';

describe('Matomo Admin > Diagnostic', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the tracking failures page correctly', async () => {
    await TrackingFailuresPage.open();

    await expect(
      await browser.checkFullPageScreen('matomo-admin.diagnostic.tracking-failures')
    ).toEqual(0);
  });

  it('should load the device detection page correctly', async () => {
    await DeviceDetectionPage.open();

    await $('body').moveTo({ xOffset: 0, yOffset: 0 });

    await expect(
      await browser.checkFullPageScreen('matomo-admin.diagnostic.device-detection')
    ).toEqual(0);
  });
});
