/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $ } from '@wdio/globals';
import MatomoAdminPage from '../matomo-admin.page.js';

export default class TagManagerPage extends MatomoAdminPage {
  async openPublishModal() {
    await browser.execute(() => {
      $('li[role=menuitem] a.item:contains(Publish)')[0].click();
    });

    await $('.modal .editVersion input').waitForExist();
  }

  async openInstallCodeModal() {
    await browser.execute(() => {
      $('li[role=menuitem] a.item:contains(Install Code)')[0].click();
    });

    await $('.modal .manageInstallTagCode pre').waitForExist();
  }

  async enablePreviewMode() {
    await browser.execute(() => {
      $('li[role=menuitem] a.item:contains(Preview)')[0].click();
    });

    await $('input#previewDebugUrl').waitForExist();
  }
}