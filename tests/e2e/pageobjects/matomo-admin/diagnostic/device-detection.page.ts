/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser, $ } from '@wdio/globals';
import MatomoAdminPage from '../../matomo-admin.page.js';

class DeviceDetectionPage extends MatomoAdminPage {
  async open() {
    const result = await super.open('DevicesDetection.detection');

    await $('textarea').waitForDisplayed();
    await browser.execute(() => {
      $('textarea').val(
        $('textarea')
          .val()
          .replace(/rv:\d+\.\d+/g, 'rv:REMOVED')
          .replace(/\/\d+\.\d+/g, '/REMOVED')
      );
    });

    return result;
  }
}

export default new DeviceDetectionPage();
