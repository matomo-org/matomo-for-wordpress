/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as fs from 'fs';
import { expect, browser } from '@wdio/globals'
import BlogHomepage from './pageobjects/blog-homepage.page.js';
import MwpSettingsPage from './pageobjects/mwp-admin/settings.page.js';
import Website from './website.js';
import path from "path";

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
    await Website.login();
  });

  after(async () => {
    await disableTagManagerTracking();
  });

  it('should correctly inject tag manager tags into blog pages', async () => {
    await enableTagManagerTracking();

    const uploadsFolder = path.join(process.cwd(), 'docker', 'wordpress', 'test', 'wp-content', 'uploads', 'matomo');
    const uploads = fs.readdirSync(uploadsFolder);
    console.log(uploads);
    const containerFileName = uploads.find((f) => /^container_.*\.js$/.test(f));
    console.log(fs.readFileSync(path.join(uploadsFolder, containerFileName)).toString('utf-8'));

    await BlogHomepage.open();

    await browser.pause(25000);

    // add the trigger element
    await browser.execute(() => {
      document.body.innerHTML += '<button id="tagmanager-test-element">TEST</button>';
    });

    // trigger the element
    await browser.execute(() => {
      document.querySelector('#tagmanager-test-element').click();
    });

    const debugValues = await browser.execute(() => {
      return {
        A: window.TAG_MANAGER_DEBUG_A,
        B: window.TAG_MANAGER_DEBUG_B,
        C: window.TAG_MANAGER_DEBUG_C,
        D: window.TAG_MANAGER_DEBUG_D,
        E: window.TAG_MANAGER_DEBUG_E,
      };
    });
    console.log(debugValues);

    // check the tag was fired
    await $('#test-tagmanager-added-div').waitForExist({ timeout: 30000 });
    const attributeValue = await browser.execute(
      () => document.querySelector('#test-tagmanager-added-div').getAttribute('var-value'),
    );
    expect(attributeValue).toEqual('test value');
  });
});
