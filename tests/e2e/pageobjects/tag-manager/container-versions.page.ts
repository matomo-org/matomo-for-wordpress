/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import TagManagerPage from './page.js';
import GlobalSetup from '../../global-setup.js';

class ContainerVersionsPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.manageVersions', {
      idContainer: GlobalSetup.testIdContainer,
    });

    await $('.tagManagerVersionList td.description').waitForDisplayed();

    await this.normalizeContainerSelector();

    await browser.execute(() => {
      $('td.created').each((i, e) => $(e).html('REMOVED'));
    });

    return result;
  }
}

export default new ContainerVersionsPage();
