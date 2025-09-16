/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Page from './page.js';

export enum Post {
  MARCH_UPDATE = 'march-update',
  WHY_USE_OUR_STUFF = 'why-use-our-stuff',
  TEN_NEW_WAYS_TO_WHATEVER = '10-new-ways-to-whatever',
  HELLO_WORLD = 'hello-world',
  ABOUT = 'about',
  CONTACT_US = 'contact-us',
  LEARN_MORE = 'learn-more',
  SAMPLE_PAGE = 'sample-page',
  SHOP = 'shop',
}

class BlogPostPage extends Page {
  open(postSlug = Post.MARCH_UPDATE) {
    return super.open(`/${postSlug}/`);
  }
}

export default new BlogPostPage();
