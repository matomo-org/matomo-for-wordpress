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
      await MatomoIni.set('WordPress', 'allow_app_password_as_token_auth', 1);
    });

    // NOTE: authenticating via header is tested implicitly by GlobalSetup
    it('should allow authenticating via app password in token_auth when feature is enabled', async () => {
      const module = 'SitesManager';
      const action = 'SitesIdWithAtLeastViewAccess';
      const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${MatomoApi.toSnakeCase(module)}/${MatomoApi.toSnakeCase(action)}`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;

      const response = await fetch(wordpressUrl, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          token_auth: userPass,
        }),
      });

      const json = await response.json();
      expect(json).toEqual(['1']);
    });

    it('should not allow using a app password as a token_auth in a non-POST request', async () => {
      const module = 'SitesManager';
      const action = 'SitesIdWithAtLeastViewAccess';
      const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${MatomoApi.toSnakeCase(module)}/${MatomoApi.toSnakeCase(action)}`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;

      try {
        await fetch(`${wordpressUrl}&token_auth=${userPass}`, {
          method: 'GET',
        });
      } catch (e) {
        expect(e).toBeInstanceOf(Error);
        expect((e as Error).message).toEqual(''); // TODO
        return;
      }

      throw new Error('did not throw');
    });
  });
});
