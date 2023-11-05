/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

class BlogPostPage extends Page {
  open() {
    return super.open('?p=1');
  }
}

export default new BlogPostPage();
