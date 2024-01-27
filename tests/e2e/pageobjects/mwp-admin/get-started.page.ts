/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import Page from '../page.js';

class MwpGetStartedPage extends Page {
  async open() {
    return await super.open('/wp-admin/admin.php?page=matomo-get-started');
  }
}

export default new MwpGetStartedPage();
