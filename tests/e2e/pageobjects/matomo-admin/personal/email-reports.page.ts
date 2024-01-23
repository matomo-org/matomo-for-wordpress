/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoAdminPage from '../../matomo-admin.page.js';

class EmailReportsPage extends MatomoAdminPage {
  async open() {
    const result = await super.open('ScheduledReports.index');
    await this.hideDateSelectorDate();
    await $('#add-report').waitForDisplayed();
    return result;
  }

  async startAddReport() {
    await browser.execute(() => {
      $('#add-report').click();
    });
    await $('#addEditReport').waitForDisplayed();
  }

  async createNewReport() {
    await browser.execute(function () {
      $('#report_description').setValue('test report');
    });
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
    await this.hideDateSelectorDate();
  }

  async downloadReport(id: string|number) {
    await $(`a[name="linkDownloadReport"][id="${id}"]`).click();

    await browser.waitUntil(async () => {
      await browser.pause(2000);
      try {
        await browser.switchWindow(/ScheduledReports\.generateReport/);
        return true;
      } catch (e) {
        return false;
      }
    }, { timeout: 15000 });

    await $('h2#UserCountry_getContinent').waitForDisplayed();

    // remove date
    await browser.execute(function () {
      document.querySelectorAll('p').forEach(function (node) {
        const text = node.innerHTML;
        if (/Date range:.*?<br>/s.test(text)) {
          node.innerHTML = text.replace(/Date range:.*?<br>/sg, 'Date range: <removed><br>');
        }
      });
    });
  }
}

export default new EmailReportsPage();
