/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import TagManagerPage from './page.js';

class GettingStartedPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.gettingStarted');

    await $('.card-content').waitForDisplayed();

    return result;
  }
}

export default new GettingStartedPage();
