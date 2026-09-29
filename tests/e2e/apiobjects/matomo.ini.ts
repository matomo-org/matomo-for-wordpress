/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import Website from '../website.js'

class MatomoIniConfig {
  async set(section: string, key: string, value: any) {
    await this.callSetConfigValue(new URLSearchParams({
      section,
      key,
      value: JSON.stringify(value),
    }));
  }

  async remove(section: string, key: string) {
    await this.callSetConfigValue(new URLSearchParams({
      section,
      key,
    }));
  }

  private async callSetConfigValue(body: URLSearchParams) {
    body.set('action', 'matomo_test_set_config_value');

    const response = await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body,
    });

    const result = await response.text();
    if (result !== '"ok"') {
      throw new Error(`failed to change config.ini.php: ${result}`);
    }
  }
}

export default new MatomoIniConfig();
