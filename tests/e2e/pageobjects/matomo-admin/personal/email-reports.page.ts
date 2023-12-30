/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../matomo-admin.page.js';

class EmailReportsPage extends MatomoAdminPage {
  open() {
    return super.open('ScheduledReports.index');
  }

  async startAddReport() {
    await $('#add-report').click();
    await $('#addEditReport').waitForDisplayed();
  }

  async createNewReport() {
    await $('#report_description').setValue('test report');
    await browser.execute(function () {
      $('#emailUserCountry_getContinent').click();
    });
    await browser.execute(function () {
      $('#emailUserCountry_getRegion').click();
    });
    await browser.execute(function () {
      $('#emailDevicesDetection_getModel').click();
    });
    await $('.matomo-save-button > input').click();
    await $('#entityEditContainer tr').waitForDisplayed();
  }

  async downloadReport(id: string|number) {
    await $(`a[name="linkDownloadReport"][id="${id}"]`).click();
    await browser.pause(1000);
    await browser.switchWindow(/ScheduledReports\.generateReport/);
    await $('h2#UserCountry_getContinent').waitForDisplayed();
  }
}

export default new EmailReportsPage();
