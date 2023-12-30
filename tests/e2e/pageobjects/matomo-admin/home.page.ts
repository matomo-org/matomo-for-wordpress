/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from './matomo-admin.page.js';

class AdminHomePage extends MatomoAdminPage {
  open() {
    return super.open('CoreAdminHome.home');
  }
}

export default new AdminHomePage();
