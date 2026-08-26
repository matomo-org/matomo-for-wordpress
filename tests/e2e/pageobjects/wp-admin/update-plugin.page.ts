/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import WpAdminPage from './page.js';
import Website from '../../website.js';

export const REQUIREMENTS_FAQ_PATH = 'what-are-the-requirements-for-matomo-for-wordpress';

export const UPDATE_RESULT_SELECTOR = '#wpbody-content .wrap';

class UpdatePluginPage extends WpAdminPage {
  /**
   * Opens an update link taken from the plugins screen.
   */
  async open(url: string) {
    const baseUrl = await Website.baseUrl();
    return await super.open(url.startsWith(baseUrl) ? url.substring(baseUrl.length) : url);
  }

  async waitForText(text: string) {
    await browser.waitUntil(
      async () => (await this.content()).includes(text),
      { timeout: 60000, timeoutMsg: `update result never contained "${text}"` }
    );
  }

  async content() {
    return await browser.execute(() => window.jQuery('#wpbody-content').text());
  }

  async hasRequirementsFaqLink() {
    return await this.hasElement(`#wpbody-content a[href*="${REQUIREMENTS_FAQ_PATH}"]`);
  }
}

export default new UpdatePluginPage();
