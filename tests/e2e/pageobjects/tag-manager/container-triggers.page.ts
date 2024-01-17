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

class ContainerTriggersPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.manageTriggers', {
      idContainer: GlobalSetup.testIdContainer,
    });

    await $('.manageTrigger td.description').waitForDisplayed();

    await browser.execute(() => {
      $('td.lastUpdated').each((i, e) => $(e).html('REMOVED'));
    });

    return result;
  }
}

export default new ContainerTriggersPage();
