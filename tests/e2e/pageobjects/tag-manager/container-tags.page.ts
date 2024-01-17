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

class ContainerTagsPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.manageTags', {
      idContainer: GlobalSetup.testIdContainer,
    });

    await $('.tagManagerTagList td.description').waitForDisplayed();

    await browser.execute(() => {
      $('td.lastUpdated').each((i, e) => $(e).html('REMOVED'));
    });

    return result;
  }
}

export default new ContainerTagsPage();
