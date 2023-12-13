/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import * as path from 'path';
import * as fs from 'fs';

let latestWordpressVersion: string|undefined;

async function getLatestWordpressVersion() {
  if (!latestWordpressVersion) {
    const response = await fetch('http://api.wordpress.org/core/version-check/1.7/');
    const json = await response.json() as any;
    latestWordpressVersion = json.offers[0].version as string;
  }

  return latestWordpressVersion;
}

class Website {
  private wpNonce: string|undefined;
  private loggedIn: boolean = false;

  async baseUrl() {
    const wordpressVersion = process.env.WORDPRESS_VERSION || (await getLatestWordpressVersion());
    const wordpressFolder = process.env.WORDPRESS_FOLDER || wordpressVersion;
    const wordpressVersionUrlPart = wordpressFolder ? `/${wordpressFolder}` : '';

    return `${process.env.WORDPRESS_URL || 'http://localhost:3000'}${wordpressVersionUrlPart}`
  }

  async login() {
    if (this.loggedIn) {
      return;
    }

    const baseUrl = await this.baseUrl();
    await browser.url(`${baseUrl}/wp-login.php`);

    await $('#user_login').setValue(process.env.WORDPRESS_USER_LOGIN || 'root');
    await $('#user_pass').setValue(process.env.WORDPRESS_USER_PASS || 'pass');
    await $('#wp-submit').click();

    await browser.waitUntil(async function () {
      return !!(await browser.execute(function () {
        return window.wpApiSettings?.nonce;
      }));
    });
  }

  async getWpNonce() {
    if (process.env.WP_APP_PASSWORD) { // TODO: documentation
      return process.env.WP_APP_PASSWORD;
    }

    // assuming local docker-compose environment
    if (!this.wpNonce) {
      const wordpressVersion = process.env.WORDPRESS_VERSION || (await getLatestWordpressVersion());
      const wordpressFolder = process.env.WORDPRESS_FOLDER || wordpressVersion;

      // using process.cwd() as __dirname is not available in wdio for some reason (except probably the conf.ts file)
      const pathToLocalAppPassword = path.join(process.cwd(), 'docker', 'wordpress', wordpressFolder, 'apppassword');
      this.wpNonce = fs.readFileSync(pathToLocalAppPassword).toString('utf-8').trim();
    }

    return this.wpNonce!;
  }
}

export default new Website();
