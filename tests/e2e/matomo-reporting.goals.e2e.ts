/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import ManageGoalsPage from './pageobjects/matomo-reporting/goals/manage-goals.page.js';
import GoalOverviewPage from './pageobjects/matomo-reporting/goals/overview.page.js';
import GoalPage from './pageobjects/matomo-reporting/goals/goal.page.js';
import Website from './website.js';

describe('Matomo Reporting > Goals', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the manage goals page correctly', async () => {
    await ManageGoalsPage.open();

    await ManageGoalsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.goals.manage-goals')
    ).toBeLessThan(0.01);
  });

  it('should load a single goal page correctly', async () => {
    await GoalPage.open();

    await GoalPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.goals.single-goal')
    ).toBeLessThan(0.2); // by dimension reports' width can be random
  });

  it('should load the goals overview page correctly', async () => {
    await GoalOverviewPage.open();

    await GoalOverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.goals.overview')
    ).toBeLessThan(0.2); // by dimension reports' width can be random
  });
});
