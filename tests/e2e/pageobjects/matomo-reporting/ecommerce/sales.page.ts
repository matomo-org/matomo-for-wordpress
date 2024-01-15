/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoReportingPage from '../../matomo-reporting.page.js';

class SalesPage extends MatomoReportingPage {
  async open() {
    return await super.open('Goals_Ecommerce.Ecommerce_Sales');
  }
}

export default new SalesPage();
