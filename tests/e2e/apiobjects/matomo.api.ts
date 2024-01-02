/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import Website from '../website.js';

function toSnakeCase(s: string) {
  return s.replace(/([A-Z])/g, '_$1').replace(/^_/, '').toLowerCase();
}

class MatomoApi {
  async track(idsite: string, params: URLSearchParams) {
    const trackingEndpoint = `${await Website.baseUrl()}/wp-content/plugins/matomo/app/matomo.php`;

    params.set('idsite', idsite);
    params.set('rec', '1');
    params.set('token_auth', await Website.getWpNonce());

    const fullUrl = `${trackingEndpoint}?${params}`;

    const response = await fetch(fullUrl, {
      method: 'GET',
    });

    const blob = Buffer.from(await response.arrayBuffer());

    const expectedGif = 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
    if (expectedGif !== blob.toString('base64')) {
      throw new Error(`tracking request failed: ${blob.toString('utf-8')}`);
    }
  }

  async call(restMethod: string, apiMethod: string, params: URLSearchParams) {
    const [module, action] = apiMethod.split('.');
    const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${toSnakeCase(module)}/${toSnakeCase(action.replace(/^get/, ''))}`;

    const fullUrl = `${wordpressUrl}&${params}`;

    const nonce = await Website.getWpNonce();
    const userPass = `root:${nonce}`;
    const response = await fetch(fullUrl, {
      method: restMethod,
      headers: {
        'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
      },
    });

    const text = await response.text();

    try {
      return JSON.parse(text);
    } catch (e) {
      throw new Error(`Failed to parse JSON response: ${text}`)
    }
  }
}

export default new MatomoApi();
