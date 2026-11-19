/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Website from './website.js';

// Run this upgrade in the serial phase. The parallel multisite UI tests share
// this installation and must not request plugin pages while its files are replaced.
describe('MultiSite Upgrade', () => {
  before(async () => {
    Website.overrideWordPressFolder(`${await Website.getWpFolder()}-multi`);
    await Website.login();
  });

  after(async () => {
    Website.unsetSite();
    Website.removeWordPressFolderOverride();
  });

  it('should succeed when updating to the current code', async () => {
    await Website.updateMatomoToLatest();
  });
});
