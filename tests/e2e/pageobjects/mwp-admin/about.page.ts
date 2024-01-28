/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MwpPage from './page.js';

class MwpAboutPage extends MwpPage {
  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-about');
  }
}

export default new MwpAboutPage();
