/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

import * as path from 'node:path';
import * as fs from 'node:fs';
import MatomoIni from './apiobjects/matomo.ini.js';
import MatomoApi from './apiobjects/matomo.api.js';
import Website from './website.js';
import GlobalSetup from './global-setup';

describe( 'Matomo API', function () {
  before(async () => {
    await MatomoIni.set('WordPress', 'allow_app_password_as_token_auth', 1);
  });

  after(async () => {
    await MatomoIni.set('WordPress', 'allow_app_password_as_token_auth', 0);
  });

  describe('Authentication', function () {
    // NOTE: authenticating via header is tested implicitly by GlobalSetup
    it('should allow authenticating via app password in token_auth when feature is enabled', async () => {
      const module = 'SitesManager';
      const action = 'SitesIdWithAtLeastViewAccess';
      const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${MatomoApi.toSnakeCase(module)}/${MatomoApi.toSnakeCase(action)}`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      // check an unauthenticated request (sanity check)
      let response = await fetch(wordpressUrl, {
        method: 'POST',
      });

      let json = await response.json();
      expect(json).toEqual([]);

      // check an authenticated request
      const userPass = `root:${nonce}`;
      response = await fetch(wordpressUrl, {
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

    it('should not allow using a app password as a token_auth in a non-POST request', async () => {
      const module = 'SitesManager';
      const action = 'SitesIdWithAtLeastViewAccess';
      const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/${MatomoApi.toSnakeCase(module)}/${MatomoApi.toSnakeCase(action)}`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;

      const response = await fetch(`${wordpressUrl}&token_auth=${userPass}`, {
        method: 'GET',
      });

      const json = await response.json();
      expect(json).toEqual({
        code: 'matomo_error',
        message: 'Invalid token auth or token auth was not provided as a POST parameter.',
        data: null,
      });
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
      expect(json).toEqual({
        message: 'Unable to authenticate with the provided token. It is either invalid or expired.',
        result: 'error',
      });

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

  describe('API Methods', function () {
    async function checkApiResponseAgainstExpected(testName: string, apiOutput: any) {
      delete apiOutput.timerMillis;

      apiOutput = JSON.stringify(apiOutput, null, 2);

      const expectedPath = path.join(process.cwd(), 'tests', 'e2e', 'baseline', 'api', `${testName}.xml`);
      const processedPath = path.join(process.cwd(), 'tests', 'e2e', 'actual', 'api', `${testName}.xml`);

      if (!fs.existsSync(processedPath)) {
        fs.mkdirSync(path.dirname(processedPath), { recursive: true });
        fs.writeFileSync(processedPath, apiOutput);
      }

      if (!fs.existsSync(expectedPath)) {
        throw new Error(`Cannot find ${testName}.xml expected API output file.`);
      }

      const expectedContents = fs.readFileSync(expectedPath).toString('utf-8');
      expect(apiOutput).toEqual(expectedContents);
    }

    it('should call API.getProcessedReport successfully when using the Matomo API directly', async () => {
      const url = `${await Website.baseUrl()}/wp-content/plugins/matomo/app/index.php?module=API&method=API.getProcessedReport&apiModule=Actions&apiAction=getPageUrls&idSite=1&date=${GlobalSetup.getDateOfVisitTrackedInPast()}&period=month&format=json`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;
      const response = await fetch(url, {
        method: 'POST',
        headers:{
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          token_auth: userPass,
        }),
      });

      const data = await response.json();
      await checkApiResponseAgainstExpected('API.getProcessedReport_direct', data);
    });

    it('should call API.getProcessedReport successfully when using the WordPress REST API', async () => {
      const wordpressUrl = `${await Website.baseUrl()}/index.php?rest_route=/matomo/v1/api/processed_report&apiModule=Actions&apiAction=getPageUrls&idSite=1&date=${GlobalSetup.getDateOfVisitTrackedInPast()}&period=month&format=json&flat=1`;

      const nonce = await Website.getWpNonce();
      if (!nonce) {
        throw new Error('No application password found!');
      }

      const userPass = `root:${nonce}`;
      const response = await fetch(wordpressUrl, {
        method: 'GET',
        headers: {
          'Authorization': `Basic ${Buffer.from(userPass).toString('base64')}`,
        },
      });

      const data = await response.json();
      await checkApiResponseAgainstExpected('API.getProcessedReport_rest', data);
    });
  });
});
