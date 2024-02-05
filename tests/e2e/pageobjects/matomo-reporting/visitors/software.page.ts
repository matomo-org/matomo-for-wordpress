/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoReportingPage from '../../matomo-reporting.page.js';

class SoftwarePage extends MatomoReportingPage {
  async open() {
    return await super.open('General_Visitors.DevicesDetection_Software');
  }
}

export default new SoftwarePage();
