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

describe('MultiSite General', function() {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await GlobalSetup.setUp();
    await Website.login();
  });

  it('should display the MWP admin pages for a single site correctly', async () => {
    // TODO
  });

  it('should display the MWP settings page for a single site correctly', async () => {
    // TODO
  });

  it('should display the Matomo reporting pages for a single site correctly', async () => {
    // TODO
  });
});

