/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import AiChatbotsOverviewPage from './pageobjects/matomo-reporting/ai-assistants/ai-chatbots-overview.page.js';
import AiChatbotsRealtimePage from './pageobjects/matomo-reporting/ai-assistants/ai-chatbots-realtime.page.js';
import AiChatbotsContentRequestsPage from './pageobjects/matomo-reporting/ai-assistants/ai-chatbots-content-requests.page.js';
import AiAgentsOverviewPage from './pageobjects/matomo-reporting/ai-assistants/ai-agents-overview.page.js';
import Website from './website.js';

describe('Matomo Reporting > AI Assistants', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the AI chatbots overview page correctly', async () => {
    await AiChatbotsOverviewPage.open();

    await AiChatbotsOverviewPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ai-assistants.ai-chatbots-overview')
    ).toBeLessThan(0.1);
  });

  it('should load the AI chatbots real-time page correctly', async () => {
    await AiChatbotsRealtimePage.open();

    await AiChatbotsRealtimePage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ai-assistants.ai-chatbots-realtime')
    ).toBeLessThan(0.1);
  });

  it('should load the AI chatbots content requests page correctly', async () => {
    await AiChatbotsContentRequestsPage.open();

    await AiChatbotsContentRequestsPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ai-assistants.ai-chatbots-content-requests')
    ).toBeLessThan(0.1);
  });

  it('should load the AI agents overview page correctly', async () => {
    await AiAgentsOverviewPage.open();

    await AiAgentsOverviewPage.prepareMatomoPageForScreenshot();
    await expect(
      await browser.checkFullPageScreen('matomo-reporting.ai-assistants.ai-agents-overview')
    ).toBeLessThan(0.1);
  });
});
