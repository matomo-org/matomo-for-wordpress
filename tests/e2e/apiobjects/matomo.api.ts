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
    params.set('token_auth', 'ignore'); // set to trigger authentication in the tracker, not actually used

    const fullUrl = `${trackingEndpoint}?${params}`;

    const nonce = await Website.getWpNonce();
    const userPass = `root:${nonce}`;

    const response = await fetch(fullUrl, {
      method: 'GET',
      headers: {
        'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
      },
    });

    const blob = Buffer.from(await response.arrayBuffer());

    const expectedGif = 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
    if (expectedGif !== blob.toString('base64')) {
      throw new Error(`tracking request failed: ${blob.toString('utf-8')}`);
    }
  }

  async call(restMethod: string, apiMethod: string, params: URLSearchParams = new URLSearchParams()) {
    const [module, action] = apiMethod.split('.');
    const wpAction = action === 'get' ? 'get' : action.replace(/^(get|add|create)/, '');
    const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${toSnakeCase(module)}/${toSnakeCase(wpAction)}`;

    const fullUrl = `${wordpressUrl}&${params}`;

    const nonce = await Website.getWpNonce();
    if (!nonce) {
      throw new Error('No application password found!');
    }

    const userPass = `root:${nonce}`;

    let result;
    let response;

    let attempts = 0;
    while (attempts < 3) {
      attempts += 1;

      try {
        response = await fetch(fullUrl, {
          method: restMethod,
          headers: {
            'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
          },
        });

        break;
      } catch (e) {
        console.log(`Failed to request ${fullUrl}: ${(e as Error).message}`);

        await new Promise((r) => setTimeout(r, 1000));

        if (attempts >= 3) {
          throw e;
        }
      }
    }

    const text = await response!.text();

    try {
      result = JSON.parse(text);
    } catch (e) {
      throw new Error(`Failed to parse JSON response: ${text}`)
    }

    if (result.result === 'error') {
      throw new Error(result.message);
    }

    if (result.code && result.message) {
      throw new Error(`${result.code}: ${result.message} (attempts = ${attempts})`);
    }

    return result;
  }
}

export default new MatomoApi();
