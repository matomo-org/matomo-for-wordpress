/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';
import GlobalSetup from '../../../global-setup';

class GoalPage extends MatomoReportingPage {
  async open() {
    return await super.open(`Goals_Goals.${GlobalSetup.testIdGoal}`);
  }
}

export default new GoalPage();
