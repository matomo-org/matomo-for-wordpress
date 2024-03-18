/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';

describe('MWP Language', () => {
  before(async () => {
    await Website.login();
  });

  it('should use the appropriate language in MWP admin when the site language changes', async () => {
    await Website.setSiteLanguage('de');

    // TODO
  });

  it('should use the appropriate language in Matomo Reporting when the site language changes', async () => {
    // TODO
  });

  it('should use the appropriate language in MWP admin when the user profile language changes', async () => {
    await Website.setUserProfileLanguage('fr');

    // TODO
  });

  it('should use the appropriate language in Matomo Reporting when the user profile language changes', async () => {
    // TODO
  });

  it('should use the appropriate language in MWP admin when a plugin calls switch_to_locale', async () => {
    // TODO (use query param mwp_switch_to_locale=jp)
  });

  it('should use the appropriate language in Matomo Reporting when a plugin calls switch_to_locale', async () => {
    // TODO (use query param mwp_switch_to_locale=jp)
  });
});
