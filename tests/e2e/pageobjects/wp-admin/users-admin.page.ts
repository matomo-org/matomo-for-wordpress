/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import WpAdminPage from './page.js';

class UsersAdminPage extends WpAdminPage {
  async open() {
    const result = await super.open('/wp-admin/users.php');
    await $('#wpbody-content').waitForExist({ timeout: 30000 });
    return result;
  }
}

export default new UsersAdminPage();
