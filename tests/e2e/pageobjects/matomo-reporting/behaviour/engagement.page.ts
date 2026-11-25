/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoReportingPage from '../../matomo-reporting.page.js';

class EngagementPage extends MatomoReportingPage {
  async open(params: Record<string, string> = {}) {
    return await super.open('General_Actions.VisitorInterest_Engagement', params);
  }
}

export default new EngagementPage();
