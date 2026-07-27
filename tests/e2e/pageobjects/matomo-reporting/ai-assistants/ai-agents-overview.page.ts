/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoReportingPage from '../../matomo-reporting.page.js';

class AiAgentsOverviewPage extends MatomoReportingPage {
  async open() {
    return await this.openWith();
  }

  async openWith(params: Record<string, string> = {}) {
    return await super.open('General_AIAssistants.AIAgents_AIAgentsOverview', params);
  }
}

export default new AiAgentsOverviewPage();
