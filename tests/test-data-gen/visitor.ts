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

  constructor() {
    this.ip = Config.ipAddresses.next().value;
    this.userAgent = Config.userAgents.next().value;
  }

  async execute() {
    visitStart(`Performing ${this.constructor.name} visit...`);

    const originalIpAddressOverride = Page.ipAddressOverride;
    const originalUserAgentOverride = Page.userAgentOverride;

    Page.ipAddressOverride = this.ip;
    Page.userAgentOverride = this.userAgent;

    try {
      await this.visit();
    } finally {
      Page.ipAddressOverride = originalIpAddressOverride;
      Page.userAgentOverride = originalUserAgentOverride;

      visitEnd();
    }
  }

  async waitForWpStatisticsTracking(expectedRequestCount = 1) {
    await browser.waitUntil(async () => {
      const requests = await browser.getRequests({ includePending: false });
      const actualRequestCount = requests
        .filter((r) => /\/wp-json\/wp-statistics\//.test(r.url));
      return actualRequestCount >= expectedRequestCount;
    }, { timeout: 30000 });

    await browser.pause(1000); // for visit length
  }

  abstract visit(): Promise<void>;
}
