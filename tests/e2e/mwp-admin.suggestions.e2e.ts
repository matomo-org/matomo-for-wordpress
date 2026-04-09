/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import {expect, browser, $} from '@wdio/globals';
import MwpSettingsPage from './pageobjects/mwp-admin/settings.page.js';
import Website from './website.js';
import SummaryPage from "./pageobjects/mwp-admin/summary.page";
import MwpSummaryPage from "./pageobjects/mwp-admin/summary.page";

describe('MWP Admin > Summary - Plugin Suggestions', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await Website.login();
  });

  const SUGGESTIONS_TO_TEST = [
    'HeatmapSessionRecording',
    'SearchEngineKeywordsPerformance',
    'AdvertisingConversionExport',
    'WpPremiumBundle',
    'UsersFlow',
    'Funnels',
  ];

  SUGGESTIONS_TO_TEST.forEach((suggestionId) => {
    it(`should display the ${suggestionId} plugin suggestion correctly`, async () => {
      await SummaryPage.forceShowSuggestion(suggestionId);

      await MwpSummaryPage.hideAllButElement('.matomo-plugin-suggestion'); // TODO
      await expect(
        await browser.checkFullPageScreen(`mwp-admin.suggestion.${suggestionId}.${process.env.PHP_VERSION}${trunkSuffix}`)
      ).toBeLessThan(0.1);
    });
  });
});
