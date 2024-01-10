/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class BlogProductPage extends Page {
  open() {
    return super.open('/?product=folding-monitors');
  }
}

export default new BlogProductPage();
