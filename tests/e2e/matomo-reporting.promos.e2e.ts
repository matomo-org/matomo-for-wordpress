/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser, $ } from '@wdio/globals';
import Website from './website.js';
import MatomoPromoPage from './pageobjects/matomo-reporting/promo.page.js';

describe('Matomo Reporting > Visitors', () => {
  before(async () => {
    await Website.login();
  });

  const PROMOS = [
    'Funnels',
    'Heatmaps',
    'SessionRecording',
    'CrashAnalytics',
    'CustomReports',
    'MediaAnalytics',
    'FormAnalytics',
  ];
  PROMOS.forEach((promo) => {
    it(`should display the ${promo} promo correctly`, async () => {
      await MatomoPromoPage.open(promo);

      const plugin = promo === 'Heatmaps' || promo === 'SessionRecording' ? 'HeatmapSessionRecording' : promo;

      // no screenshot testing since we depend on what is in core
      expect(await $('.pluginPromo').isExisting()).toBeTruthy();

      const unlockUrl = await browser.execute(() => $('.pluginPromo .promo-actions a:not(.learn-more)').attr('href'));
      expect(unlockUrl).toEqual(`https://plugins.matomo.org/${plugin}?add-to-cart=ws&currency=EUR&wp=1`);

      const learnMoreUrl = await browser.execute(() => $('.pluginPromo .promo-actions a.learn-more').attr('href'));
      expect(learnMoreUrl).toEqual(`https://plugins.matomo.org/${plugin}?currency=EUR&wp=1`);
    });
  });

  it('should dismiss the promo when the hide link is clicked', async () => {
    await MatomoPromoPage.open('Funnels');
    await MatomoPromoPage.dismiss(); // hides dashboard after redirect for cleaner screenshot

    await MatomoPromoPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`matomo-reporting.promo.dismissed`)
    ).toBeLessThanOrEqual(0.05);
  });
});
