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
  async normalizeContainerSelector() {
    await browser.execute(() => {
      $('.tagContainerSelector a.title').html(
        $('.tagContainerSelector a.title').html().replace(/\([A-Za-z0-9]+\)/g, '')
      );
    });
  }

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
    await $('.modal tr').waitForExist();

    await browser.execute(() => {
      $('td.released_on').each((i, e) => $(e).html('REMOVED'));
    });

    await browser.execute(() => {
      $('.modal .manageInstallTagCode pre').html(
        $('.modal .manageInstallTagCode pre').html().replace(/container_[a-zA-Z0-9_]+\.js/g, 'container_REMOVED.js')
      );
    });
  }

  async enablePreviewMode() {
    await browser.execute(() => {
      $('li[role=menuitem] a.item:contains(Preview)')[0].click();
    });

    await $('input#previewDebugUrl').waitForExist();
    await $('td.lastUpdated').waitForExist();

    const notificationsModified = await browser.execute(() => {
      let notificationsModified = 0;
      $('.notification-body').each((i, e) => {
        $(e).html(
          $(e).html().replace(/mtmPreviewMode=([a-zA-Z0-9]+)/g, 'mtmPreviewMode=REMOVED'),
        );

        notificationsModified += 1;
      });
      return notificationsModified;
    });

    if (!notificationsModified) {
      throw new Error('did not modify preview notification');
    }

    await this.normalizeContainerSelector();

    await browser.execute(() => {
      $('td.lastUpdated').each((i, e) => $(e).html('REMOVED'));
    });
  }
}
