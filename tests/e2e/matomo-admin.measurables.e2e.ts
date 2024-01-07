/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import GoalsAdminPage from './pageobjects/matomo-admin/measurables/goals.page.js';
import CustomDimensionsAdminPage from './pageobjects/matomo-admin/measurables/custom-dimensions.page.js';
import Website from './website.js';

describe('Matomo Admin > Measurables', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the goals page correctly', async () => {
    await GoalsAdminPage.open();

    await GoalsAdminPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-admin.measurables.goals')
    ).toEqual(0);
  });

  it('should load the custom dimensions page correctly', async () => {
    await CustomDimensionsAdminPage.open();

    await CustomDimensionsAdminPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-admin.measurables.custom-dimensions')
    ).toEqual(0);
  });
});
