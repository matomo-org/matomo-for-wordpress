/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * Records which release zip a WordPress install has the matomo plugin installed from, so that
 * both the browser driven update (tests/e2e/update.e2e.ts) and the main wdio run's onPrepare
 * hook (tests/e2e/release-setup.ts) can tell whether the code under test is installed.
 *
 * A sentinel rather than a version comparison: the version in this checkout's matomo.php can
 * legitimately be the same as the current wordpress.org stable version right after a release,
 * which would make the two builds indistinguishable.
 *
 * This module deliberately has no imports, so both website.ts and release-setup.ts can use it
 * without creating an import cycle.
 */

import * as path from 'path';
import * as fs from 'fs';

const SENTINEL_FILE_NAME = '.e2e-release-installed';

function releaseFingerprint(pathToRelease: string) {
  const stats = fs.statSync(pathToRelease);
  return `${stats.size}:${stats.mtimeMs}`;
}

export function isReleaseInstalled(pathToPlugin: string, pathToRelease: string) {
  const pathToSentinel = path.join(pathToPlugin, SENTINEL_FILE_NAME);
  if (!fs.existsSync(pathToSentinel)) {
    return false;
  }

  return fs.readFileSync(pathToSentinel).toString('utf-8').trim() === releaseFingerprint(pathToRelease);
}

export function markReleaseInstalled(pathToPlugin: string, pathToRelease: string) {
  if (!fs.existsSync(pathToPlugin)) {
    return;
  }

  fs.writeFileSync(path.join(pathToPlugin, SENTINEL_FILE_NAME), releaseFingerprint(pathToRelease));
}
