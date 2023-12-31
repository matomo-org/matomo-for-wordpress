/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../../matomo-admin.page';

class PersonalSettingsPage extends MatomoAdminPage {
  open() {
    return super.open('UsersManager.userSettings');
  }
}

export default new PersonalSettingsPage();
