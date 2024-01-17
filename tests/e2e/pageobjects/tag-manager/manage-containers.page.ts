/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import TagManagerPage from './page.js';

class ManageContainersPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.manageContainers');

    await $('.tagManagerContainerList td.description').waitForDisplayed();

    $('.tagManagerContainerList td.index').each((i, e) => $(e).html('REMOVED'));
    $('.tagManagerContainerList td.created').each((i, e) => $(e).html('REMOVED'));

    return result;
  }
}

export default new ManageContainersPage();
