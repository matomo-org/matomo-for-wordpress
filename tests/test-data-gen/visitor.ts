/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Config from './config.js';
import Page from '../e2e/pageobjects/page.js';
import { visitStart, visitEnd } from './log.js';

export default abstract class Visitor {

  private ip: string;
  private userAgent: string;
  private referrer: string;

  constructor() {
    this.ip = Config.ipAddresses.next().value;
    this.userAgent = Config.userAgents.next().value;
    this.referrer = Config.referrers.next().value;
  }

  async execute() {
    visitStart(`Performing ${this.constructor.name} visit...`);

    const originalIpAddressOverride = Page.ipAddressOverride;
    const originalUserAgentOverride = Page.userAgentOverride;
    const originalReferrerOverride = Page.referrerOverride;

    Page.ipAddressOverride = this.ip;
    Page.userAgentOverride = this.userAgent;
    Page.referrerOverride = this.referrer;

    try {
      await browser.deleteCookies();
      await browser.execute(() => {
        window.sessionStorage.clear();
      });

      await this.visit();
    } finally {
      Page.ipAddressOverride = originalIpAddressOverride;
      Page.userAgentOverride = originalUserAgentOverride;
      Page.referrerOverride = originalReferrerOverride;

      visitEnd();
    }
  }

  async waitForWpStatisticsTracking(expectedRequestCount = 1) {
    await browser.waitUntil(async () => {
      const requests = await browser.getRequests({ includePending: false });
      const actualRequestCount = requests
        .filter((r) => /\/wp-json\/wp-statistics\//.test(r.url))
        .length;
      return actualRequestCount >= expectedRequestCount;
    }, { timeout: 120000 });

    await browser.pause(1000); // for visit length
  }

  abstract visit(): Promise<void>;
}
