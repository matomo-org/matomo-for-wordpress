/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class GetStartedPage extends Page {
  open() {
    return super.open('wp-admin/admin.php?page=matomo-get-started');
  }
}

export default new GetStartedPage();
