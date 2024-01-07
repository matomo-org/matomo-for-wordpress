/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import AllChannelsPage from './pageobjects/matomo-reporting/acquisition/all-channels.page.js';
import CampaignUrlBuilderPage from './pageobjects/matomo-reporting/acquisition/campaign-url-builder.page.js';
import CampaignsPage from './pageobjects/matomo-reporting/acquisition/campaigns.page.js';
import OverviewPage from './pageobjects/matomo-reporting/acquisition/overview.page.js';
import SearchEnginesKeywordsPage from './pageobjects/matomo-reporting/acquisition/search-engines-keywords.page.js';
import SocialNetworksPage from './pageobjects/matomo-reporting/acquisition/social-networks.page.js';
import WebsitesPage from './pageobjects/matomo-reporting/acquisition/websites.page.js';
import Website from './website.js';

describe('Matomo Reporting > Acquisition', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the all channels page correctly', async () => {
    await AllChannelsPage.open();

    await AllChannelsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.all-channels')
    ).toBeLessThan(0.1);
  });

  it('should load the campaign url builder page correctly', async () => {
    await CampaignUrlBuilderPage.open();

    await CampaignUrlBuilderPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.campaign-url-builder')
    ).toBeLessThan(0.1);
  });

  it('should load the campaigns page correctly', async () => {
    await CampaignsPage.open();

    await CampaignsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.campaigns')
    ).toBeLessThan(0.1);
  });

  it('should load the overview page correctly', async () => {
    await OverviewPage.open();

    await OverviewPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.overview')
    ).toBeLessThan(0.1);
  });

  it('should load the search engine keywords page correctly', async () => {
    await SearchEnginesKeywordsPage.open();

    await SearchEnginesKeywordsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.search-engine-keywords')
    ).toBeLessThan(0.1);
  });

  it('should load the social networks page correctly', async () => {
    await SocialNetworksPage.open();

    await SocialNetworksPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.social-networks')
    ).toBeLessThan(0.1);
  });

  it('should load the websites page correctly', async () => {
    await WebsitesPage.open();

    await WebsitesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.acquisition.websites')
    ).toBeLessThan(0.1);
  });
});
