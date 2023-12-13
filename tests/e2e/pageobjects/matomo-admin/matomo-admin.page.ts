/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import * as querystring from 'querystring';
import Page from '../page.js';

export default class MatomoAdminPage extends Page{
  async open(method: string, params: Record<string, string> = {}) {
    const [module, action] = method.split('.');

    const query = querystring.stringify({
      idSite: 1,
      period: 'day',
      date: 'yesterday',
      ...params,
      module,
      action,
    });

    const result = await super.open(`/wp-content/plugins/matomo/app/index.php?${query}`);

    await this.removePhpEolWarning();

    return result;
  }

  async removePhpEolWarning() {
    await browser.execute(function () {
      jQuery('.notification').each(function () {
        if ($(this).text().includes('You must upgrade your PHP version')) {
          $(this).hide();
        }
      });
    });
  }
}
