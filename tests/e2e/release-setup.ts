/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'path';
import * as fs from 'fs';
import WpCli from './apiobjects/wp.cli.js';
import Website from './website.js';
import { isReleaseInstalled, markReleaseInstalled } from './release-sentinel.js';

/**
 * The e2e environment installs the latest stable version from wordpress.org when
 * INSTALLING_FROM_ZIP=1, and tests/e2e/update.e2e.ts then updates it to the release built from
 * this checkout. Since update.e2e.ts runs in the tracking wdio run, the main run has to be able
 * to do the same thing itself, otherwise a failure in the tracking run leaves it screenshotting
 * the stable plugin rather than the code under test.
 */
class ReleaseSetup {
  private isSetUp = false;

  async setUp() {
    if (this.isSetUp) {
      return;
    }

    this.isSetUp = true;

    if (process.env.INSTALLING_FROM_ZIP !== '1') {
      return; // the repository itself is symlinked in as the matomo plugin
    }

    const pathToRelease = process.env.RELEASE_ZIP;
    if (!pathToRelease || !fs.existsSync(pathToRelease)) {
      console.log('[release-setup] INSTALLING_FROM_ZIP is set but RELEASE_ZIP does not point at an existing'
        + ' file, not checking whether the release under test is installed.');
      return;
    }

    const pathToPlugin = await this.pathToMatomoPlugin();

    // never replace a symlinked plugin folder, `wp plugin install --force` would turn a
    // developer's live checkout into a copy
    if (fs.existsSync(pathToPlugin) && fs.lstatSync(pathToPlugin).isSymbolicLink()) {
      console.log('[release-setup] the matomo plugin folder is a symlink, leaving it alone.');
      return;
    }

    if (isReleaseInstalled(pathToPlugin, pathToRelease)) {
      return;
    }

    console.log('[release-setup] the release under test is not installed, installing it now...');

    await WpCli.installPlugins([pathToRelease], { force: true, activate: true });
    markReleaseInstalled(pathToPlugin, pathToRelease);
    await WpCli.matomoUpdate();
  }

  async pathToMatomoPlugin() {
    return path.join(process.cwd(), 'docker', 'wordpress', await Website.getWpFolder(), 'wp-content', 'plugins', 'matomo');
  }
}

export default new ReleaseSetup();
