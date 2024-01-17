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

class ContainerDashboardPage extends TagManagerPage {
  async open() {
    const result = await super.open('TagManager.dashboard', {
      idContainer: GlobalSetup.testIdContainer,
    });

    await $('.containerDashboard .card-content').waitForDisplayed();

    return result;
  }
}

export default new ContainerDashboardPage();