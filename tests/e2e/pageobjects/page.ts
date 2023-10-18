/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import Website from '../website.js';

export default class Page {
  async open(path: string) {
    const baseUrl = await Website.baseUrl();
    return browser.url(`${baseUrl}${path}`);
  }

  waitForTrackingRequest() {
    return browser.waitUntil(async function() {
      const requests = await browser.getRequests({ includePending: true });
      const trackingRequests = requests.filter((r) => /\/matomo\.php/.test(r.url));
      if (!trackingRequests.length) {
        return false;
      }

      const incompleteTrackingRequests = trackingRequests.filter((r) => r.pending);
      if (incompleteTrackingRequests.length) {
        return false;
      }

      return true;
    });
  }
}
