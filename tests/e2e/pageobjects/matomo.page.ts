/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import Page from './page.js';
import querystring from "querystring";
import * as path from "path";

export default class MatomoPage extends Page {

  async open(path: string) {
    const result = super.open(path);
    await this.waitForLoading();
    await this.addStylesToPage(`
      table.entityTable tbody tr:hover td { background-color: unset !important; }

      .dataTableVizEvolution {
        max-height: 275px !important;
      }
    `);
    await this.removeWhatsNewIfPresent();
    return result;
  }

  async removeWhatsNewIfPresent() {
    let exists = false;

    try {
      await $('.whatisnew').waitForExist({ timeout: 5000 });
      exists = true;
    } catch (e) {
      // ignore
    }

    if (exists) {
      await browser.execute(() => {
        window.jQuery('.whatisnew').closest('.ui-dialog').find('.ui-dialog-titlebar-close')[0].click();
      });
      await browser.waitUntil(async () => {
        return await browser.execute(() => window.jQuery('.whatisnew').length === 0);
      });
    }

    await browser.execute(() => {
      window.jQuery('nav .badge-menu-item-container').closest('li').hide();
    });
  }

  async waitForLoading() {
    try {
      await browser.waitUntil(async () => {
        const loadingGifs = await browser.execute(() => $('.loadingPiwik:visible').length);
        return loadingGifs === 0;
      }, { timeout: 30000 });
    } catch (e: any) {
      if (!/condition timed out/i.test(e.message)) { // don't fail the whole test if this times out for some reason
        throw e;
      }
    }
  }

  async unfocus() {
    await browser.execute(() => {
      $('#logo')[0].scrollIntoView();
    });

    // try to move mouse to another element to trigger mouseleave events
    // for code with hover effects
    try {
      await $('#logo').moveTo();
    } catch (e) {
      // ignore
    }

    await browser.execute(() => {
      $('#logo')[0].focus();
    });

    await browser.pause(250);
  }

  async hideDateSelectorDate() {
    await browser.execute(function () {
      $('#periodString a#date').text('');
    });
  }
}
