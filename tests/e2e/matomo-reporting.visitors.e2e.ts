/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import DevicesPage from './pageobjects/matomo-reporting/visitors/devices.page.js';
import LocationsPage from './pageobjects/matomo-reporting/visitors/locations.page.js';
import OverviewPage from './pageobjects/matomo-reporting/visitors/overview.page.js';
import RealTimePage from './pageobjects/matomo-reporting/visitors/real-time.page.js';
import RealTimeMapPage from './pageobjects/matomo-reporting/visitors/real-time-map.page.js';
import SoftwarePage from './pageobjects/matomo-reporting/visitors/software.page.js';
import TimesPage from './pageobjects/matomo-reporting/visitors/times.page.js';
import UserIdsPage from './pageobjects/matomo-reporting/visitors/user-ids.page.js';
import VisitsLogPage from './pageobjects/matomo-reporting/visitors/visits-log.page.js';
import Website from './website.js';

describe('Matomo Reporting > Visitors', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the devices page correctly', async () => {
    await DevicesPage.open();

    await DevicesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.devices')
    ).toBeLessThan(0.1);
  });

  it('should load the locations page correctly', async () => {
    await LocationsPage.open();

    await LocationsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.locations')
    ).toBeLessThan(0.1);
  });

  it('should load the overview page correctly', async () => {
    await OverviewPage.open();

    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.overview')
    ).toBeLessThan(0.1);
  });

  it('should load the real-time page correctly', async () => {
    await RealTimePage.open();

    await RealTimePage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.real-time')
    ).toBeLessThan(0.1);
  });

  it('should load the real-time map page correctly', async () => {
    await RealTimeMapPage.open();

    await RealTimeMapPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.real-time-map')
    ).toBeLessThan(0.1);
  });

  it('should load the software page correctly', async () => {
    await SoftwarePage.open();

    await SoftwarePage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.software')
    ).toBeLessThan(0.1);
  });

  it('should load the times page correctly', async () => {
    await TimesPage.open();

    await TimesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.times')
    ).toBeLessThan(0.1);
  });

  it('should load the user ID page correctly', async () => {
    await UserIdsPage.open();

    await UserIdsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.user-ids')
    ).toBeLessThan(0.1);
  });

  it('should load the visits log page correctly', async () => {
    await VisitsLogPage.open();

    await VisitsLogPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.visitors.visits-log')
    ).toBeLessThan(0.1);
  });
});
