/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

import MatomoIni from './apiobjects/matomo.ini.js';
import MatomoApi from './apiobjects/matomo.api.js';
import Website from './website.js';

describe( 'Matomo API', function () {
  describe('Authentication', function () {
    before(async () => {
      await MatomoIni.set('WordPress', 'allow_app_password_as_token_auth', 1);
    });

    after(async () => {
      await MatomoIni.set('WordPress', 'allow_app_password_as_token_auth', 0);
    });

    it('should be possible to use app passwords via Authorization HTTP header to the REST endpoint', async () => {
      const url = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/sites_manager/sites_id_with_at_least_view_access&idSite=1`;

      const nonce = await Website.getWpNonce(); // TODO: should this be called a nonce or app password? can't remember what terminology WP uses
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;
      const response = await fetch(url, {
        method: 'GET',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
        },
      });

      const json = await response.json();
      expect(json).toEqual(['1']);
    });

    it('should be possible to send API requests to the Matomo API endpoint with app passwords in an HTTP Authorization header', async () => {
      const url = `${await Website.baseUrl()}/wp-content/plugins/matomo/app/index.php?module=API&method=SitesManager.getSitesIdWithAtLeastViewAccess&format=json`;

      const nonce = await Website.getWpNonce(); // TODO: should this be called a nonce or app password? can't remember what terminology WP uses
      if (!nonce) {
        throw new Error('No application password found!');
      }

      // check it fails without an authorization or token_auth
      let response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
      });

      let json = await response.json();
      expect(json).toEqual({
        message: 'Wordpress_TokenAuthMissing',
        result: 'error',
      });

      // check it fails with an incorrect authorization
      response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': `Basic ${Buffer.from('root:blah').toString('base64')}`,
        },
      });

      json = await response.json();
      expect(json).toEqual([]);

      // check it works with a correct token
      const userPass = `root:${nonce}`;
      response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
        },
      });

      json = await response.json();
      expect(json).toEqual(['1']);
    });

    it('should be possible to send API requests to the Matomo API endpoint', async () => {
      const url = `${await Website.baseUrl()}/wp-content/plugins/matomo/app/index.php?module=API&method=SitesManager.getSitesIdWithAtLeastViewAccess&format=json`;

      const nonce = await Website.getWpNonce(); // TODO: should this be called a nonce or app password? can't remember what terminology WP uses
      if (!nonce) {
        throw new Error('No application password found!');
      }

      // check an unauthenticated request (sanity check)
      const userPass = `root:${nonce}`;
      let response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          token_auth: 'wrong:token',
        }),
      });

      let json = await response.json();
      expect(json.result).toEqual('error');
      expect(
        json.message === 'Unable to authenticate with the provided token. It is either invalid or expired.'
        || json.message === 'Unable to authenticate with the provided token. It is either invalid, expired or is required to be sent as a POST parameter.'
      ).toBeTruthy();

      // check an authenticated request
      response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          token_auth: userPass,
        }),
      });

      json = await response.json();
      expect(json).toEqual(['1']);
    });

    it('should not be allowed to send an app password to the Matomo API endpoint as a GET request query parameter', async () => {
      const url = `${await Website.baseUrl()}/wp-content/plugins/matomo/app/index.php?module=API&method=SitesManager.getSitesIdWithAtLeastViewAccess&format=json`;

      const nonce = await Website.getWpNonce(); // TODO: should this be called a nonce or app password? can't remember what terminology WP uses
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;
      const response = await fetch(`${url}&token_auth=${userPass}`, {
        method: 'GET',
      });

      const json = await response.json();
      expect(json).toEqual({
        message: 'Invalid token auth or token auth was not provided as a POST parameter.',
        result: 'error',
      });
    });
  });
});
