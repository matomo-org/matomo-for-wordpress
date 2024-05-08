/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { browser } from '@wdio/globals';
import { config as baseConfig } from './wdio.conf.js';

const REQUIRED_ENV_VARS = {
  WORDPRESS_URL: 'the URL to the WordPress install to run tests against',
  WP_APP_PASSWORD: 'a WordPress App Password that can be used to authenticate REST API and tracking requests',
  RELEASE_ZIP: 'the path to the Matomo for WordPress archive to test against',
  WORDPRESS_USER_LOGIN: 'the WordPress superuser login for the install to run tests against',
  WORDPRESS_USER_PASS: 'the WordPress superuser password for the install to run tests against',
};

Object.entries(REQUIRED_ENV_VARS).forEach(([name, description]) => {
  if (!process.env[name]) {
    throw new Error(`Missing environment var: ${name}\nIt should be set in the .env file to ${description}.`);
  }
});

// wdio and docker handle .env files differently. docker allows interpolation via `$var`, but wdio doesn't.
// for docker, '$' chars must be escaped with a slash, ie '\$', but wdio will just include the slash.
// so, here, we replace any occurrences of \$ with $, to get the same value that docker sees.
process.env.WORDPRESS_USER_PASS = process.env.WORDPRESS_USER_PASS.replace(/\\\$/g, '$');

let oldCheckFullpageScreen;

export const config = {
  ...baseConfig,
  maxInstances: 1,
  exclude: [],
  specs: [...baseConfig.exclude, ...baseConfig.specs],
  before: [
    baseConfig.before,
    async function () {
      oldCheckFullpageScreen = browser.checkFullPageScreen;

      // overwrite browser.checkFullPageScreen so it never returns a failure
      // (this is to avoid spamming the output with failures, since the expected screenshots
      // of WordPress run through docker will not match a hosted WordPress)
      browser.checkFullPageScreen = async function (...args) {
        await oldCheckFullpageScreen.call(this, ...args);
        return 0;
      };
    },
  ],
  after: async function () {
    browser.checkFullPageScreen = oldCheckFullpageScreen;
  },
};
