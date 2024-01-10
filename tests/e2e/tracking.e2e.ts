/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import BlogHomepage from './pageobjects/blog-homepage.page.js';
import BlogPostPage from './pageobjects/blog-post.page.js';
import MatomoApi from './apiobjects/matomo.api.js';

describe('Tracking', () => {
  it('should track pageviews using the JS client', async () => {
    const countersBefore = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(countersBefore).toHaveLength(1);

    await BlogHomepage.open();
    await BlogHomepage.waitForTrackingRequest();

    await BlogPostPage.open();
    await BlogPostPage.waitForTrackingRequest();

    await browser.pause(1500); // just to make sure everything gets tracked

    const counters = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(counters).toEqual([{
      visits: `${parseInt(countersBefore[0].visits, 10) + 1}`,
      actions: `${parseInt(countersBefore[0].actions, 10) + 2}`,
      visitors: `${parseInt(countersBefore[0].visitors, 10) + 1}`,
      visitsConverted: `${parseInt(countersBefore[0].visitors, 10) + 2}`,
    }]);
  });
});
