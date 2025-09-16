/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Config from '../config.js';
import Visitor from '../visitor.js';
import BlogHomepagePage from '../../e2e/pageobjects/blog-homepage.page.js';
import BlogPostPage from '../../e2e/pageobjects/blog-post.page.js';
import BlogCategoriesPage from '../../e2e/pageobjects/blog-categories.page.js';

export default class BlogPageviews extends Visitor {
  async visit() {
    await BlogHomepagePage.open();
    await this.waitForWpStatisticsTracking();

    const post1 = Config.posts.next().value;
    await BlogPostPage.open(post1);
    await this.waitForWpStatisticsTracking();

    await BlogCategoriesPage.open();
    await this.waitForWpStatisticsTracking();

    const post2 = Config.posts.next().value;
    await BlogPostPage.open(post2);
    await this.waitForWpStatisticsTracking();
  }
}
