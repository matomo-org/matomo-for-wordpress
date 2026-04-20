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

    const pathToRelease = path.join(process.cwd(), 'matomo-test.zip');
    if (!fs.existsSync(pathToRelease)) {
      throw new Error(`Could not find built release at ${pathToRelease}.`);
    }

    const renamedPath = path.join(process.cwd(), 'matomo.zip');
    fs.renameSync(pathToRelease, renamedPath);

    return renamedPath;
  }

  buildMarketplaceRelease() {
    const pathToRelease = path.join(process.cwd(), 'marketplace', 'matomo-marketplace-for-wordpress.test.zip');
    if (fs.existsSync(pathToRelease)) {
      fs.unlinkSync(pathToRelease);
    }

    const command = 'RELEASE_NAME=test npm run release:build';
    const o = execSync(command, { cwd: path.join(process.cwd(), 'marketplace') });

    if (!fs.existsSync(pathToRelease)) {
      throw new Error(`Could not find built release at ${pathToRelease}.`);
    }

    return pathToRelease;
  }

  async call(commandName: string, params: Record<string, string>) {
    let command = commandName;
    for (let name of Object.keys(params)) {
      command += ` ${name}${params[name] ? `=${params[name]}` : ''}`;
    }
    command = `docker compose --env-file .env.default --env-file .env run --rm exec matomo:console ${command}`;

    try {
      execSync(command);
    } catch (e) {
      console.log(e.stdout.toString());
      console.log(e.stderr.toString());
      throw e;
    }
  }
}

export default new MatomoCli();
