/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MwpPage from '../mwp-admin/page.js';

class NetworkHelpPage extends MwpPage {
  async open() {
    return super.open('/wp-admin/network/admin.php?page=matomo-marketplace');
  }
}

export default new NetworkHelpPage();
