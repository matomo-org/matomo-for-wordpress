/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import BlogHomepage from './pageobjects/blog-homepage.page.js';
import MwpSettingsPage from './pageobjects/mwp-admin/settings.page.js';
import Website from './website.js';

describe('Tracking - Tag Manager', () => {
  async function enableTagManagerTracking() {
    await MwpSettingsPage.open();
    await MwpSettingsPage.enableTagManagerTracking();
  }

  async function disableTagManagerTracking() {
    await MwpSettingsPage.open();
    await MwpSettingsPage.disableTagManagerTracking();
  }

  before(async () => {
    await Website.deleteAllCookies();
    await enableTagManagerTracking();
  });

  after(async () => {
    await disableTagManagerTracking();
  });

  it('should correctly inject tag manager tags into blog pages', async () => {
    await BlogHomepage.open();

    // add the trigger element
    await browser.execute(() => {
      document.append('<button id="tagmanager-test-element">TEST</button>')
    });

    // trigger the element
    await browser.execute(() => {
      window.jQuery('#tagmanager-test-element').click();
    });

    // check the tag was fired
    await $('#test-tagmanager-added-div').waitForExist();
    const attributeValue = await browser.execute(
      () => window.jQuery('#test-tagmanager-added-div').attr('var-value'),
    );
    expect(attributeValue).toEqual('test value');
  });
});
