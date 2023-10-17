/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import Website from '../website';

function toSnakeCase(s: string) {
  return s.replace(/([A-Z])/g, '_$1').replace(/^_/, '').toLowerCase();
}

class MatomoApi {
  async call(restMethod: string, apiMethod: string, params: URLSearchParams) {
    const [module, action] = apiMethod.split('.');
    const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${toSnakeCase(module)}/${toSnakeCase(action)}`;

    const fullUrl = `${wordpressUrl}&${params}`;

    const response = await fetch(fullUrl, {
      method: restMethod,
      headers: {
        'X-WP-Nonce': await Website.getWpNonce(),
      },
    });

    return await response.json();
  }
}

export default new MatomoApi();
