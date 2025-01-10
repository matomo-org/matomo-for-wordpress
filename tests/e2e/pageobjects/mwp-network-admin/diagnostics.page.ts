/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { MwpDiagnosticsPageType } from '../mwp-admin/diagnostics.page.js';

class NetworkDiagnosticsPage extends MwpDiagnosticsPageType {
  async open() {
    return super.open('/wp-admin/network/admin.php?page=matomo-systemreport');
  }
}

export default new NetworkDiagnosticsPage();
