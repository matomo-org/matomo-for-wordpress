/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import Page from './page.js';

class BlogHomepagePage extends Page {
  open() {
    return super.open('');
  }
}

export default new BlogHomepagePage();
