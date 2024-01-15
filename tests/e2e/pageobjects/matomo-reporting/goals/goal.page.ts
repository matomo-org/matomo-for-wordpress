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
    // enforce uniform column size in by dimension report
    await this.addStylesToPage(`
    .dimensionReport .dataTable td, .dimensionReport .dataTable th {
      width: 105px !important;
    }
    `);
    return result;
  }
}

export default new GoalPage();
