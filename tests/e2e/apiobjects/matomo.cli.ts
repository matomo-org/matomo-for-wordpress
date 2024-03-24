/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'path';
import * as fs from 'fs';
import { execSync } from 'child_process';

class MatomoCli {
  buildRelease() {
    const command = 'npm run matomo:console -- wordpress:build-release --zip --name=test';
    execSync(command);

    const pathToRelease = path.join(__dirname, '..', '..', '..', 'matomo-test.zip');
    if (!fs.existsSync(pathToRelease)) {
      throw new Error(`Could not find built release at ${pathToRelease}.`);
    }

    return pathToRelease;
  }
}

export default new MatomoCli();
