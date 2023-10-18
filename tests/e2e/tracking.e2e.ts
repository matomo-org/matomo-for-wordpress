/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect } from '@wdio/globals'
import BlogHomepage from './pageobjects/blog-homepage.page.js';
import BlogPostPage from './pageobjects/blog-post.page.js';
import MatomoApi from './apiobjects/matomo.api.js';

describe('Tracking', () => {
  it('should track pageviews using the JS client', async () => {
    const countersBefore = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(countersBefore).toEqual([{
      visits: '0',
      actions: 0,
      visitors: 0,
      visitsConverted: 0,
    }]);

    await BlogHomepage.open();
    await BlogHomepage.waitForTrackingRequest();

    await BlogPostPage.open();
    await BlogPostPage.waitForTrackingRequest();

    const counters = await MatomoApi.call('GET', 'Live.getCounters', new URLSearchParams({
      idSite: '1',
      lastMinutes: '60',
    }));

    expect(counters).toEqual([{
      visits: 1,
      actions: 2,
      visitors: 1,
      visitsConverted: 0,
    }]);
  });
});
