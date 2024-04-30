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
import GlobalSetup from './global-setup.js';

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
    await GlobalSetup.setUp();
    await Website.deleteAllCookies();
    await Website.login();
  });

  after(async () => {
    await disableTagManagerTracking();
  });

  it('should correctly inject tag manager tags into blog pages', async () => {
    await enableTagManagerTracking();

    await BlogHomepage.open();

    await browser.pause(2000);

    // add the trigger element
    await browser.execute(() => {
      document.body.innerHTML += '<button id="tagmanager-test-element">TEST</button>';
    });

    // trigger the element
    await browser.execute(() => {
      document.querySelector('#tagmanager-test-element').click();
    });

    // check the tag was fired
    await $('#test-tagmanager-added-div').waitForExist({ timeout: 30000 });
    const attributeValue = await browser.execute(
      () => document.querySelector('#test-tagmanager-added-div').getAttribute('var-value'),
    );
    expect(attributeValue).toEqual('test value');
  });
});
