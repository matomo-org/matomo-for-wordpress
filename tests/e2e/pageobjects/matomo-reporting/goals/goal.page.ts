/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';
import GlobalSetup from '../../../global-setup.js';

class GoalPage extends MatomoReportingPage {
  async open() {
    const result = await super.open(`Goals_Goals.${GlobalSetup.testIdGoal}`);
    // sometimes the column widths are just a little different in the by dimension report
    // maybe a pause will fix it
    await browser.pause(1000);
    return result;
  }
}

export default new GoalPage();
