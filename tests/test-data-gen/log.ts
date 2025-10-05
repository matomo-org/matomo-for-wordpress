/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import Config from './config';

export function visitStart(message: string) {
  if (Config.verbose) {
    process.stdout.write(message);
  }
}

export function visitEnd() {
  if (Config.verbose) {
    process.stdout.write("done\n");
  } else {
    process.stdout.write(".");
  }
}
