/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals'
import Website from './website.js';
import GlobalSetup from './global-setup.js';

describe('Network Admin', function() {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await GlobalSetup.setUp();
    await Website.login();
  });

  it('should display the multisite get started page correctly', async () => {
    // TODO
  });

  // settings, diagnostics, help, marketplace
  it('should display the multisite settings page correctly', async () => {
    // TODO
  });

  it('should display the multisite diagnostics page correctly', async () => {
    // TODO
  });

  it('should display the multisite help page correctly', async () => {
    // TODO
  });

  it('should display the multisite marketplace page correctly', async () => {
    // TODO
  });
});
