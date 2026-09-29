/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'path';
import { execFileSync } from 'child_process';
import Website from '../website.js';

const DOCUMENT_ROOT = process.env.DOCUMENT_ROOT || '/var/www/html';
const REPO_DIR_IN_CONTAINER = `${DOCUMENT_ROOT}/matomo-for-wordpress`;

interface InstallPluginOptions {
  activate?: boolean;
  force?: boolean;
}

interface PluginListEntry {
  name: string;
  status: string;
  version: string;
}

class WpCli {

  toContainerPath(pathInRepo: string) {
    return `${REPO_DIR_IN_CONTAINER}/${path.relative(process.cwd(), pathInRepo).split(path.sep).join('/')}`;
  }

  async call(args: string[]) {
    // WORDPRESS_FOLDER is passed explicitly since the exec service only reads it from
    // .env.default/.env, and scripts/wdio.sh puts it in .env.script.
    const command = [
      'compose',
      '--env-file', '.env.default',
      '--env-file', '.env',
      'run', '--rm',
      '-e', `WORDPRESS_FOLDER=${await Website.getWpFolder()}`,
      'exec', 'wp',
      ...args,
    ];

    try {
      return execFileSync('docker', command).toString('utf-8');
    } catch (e: any) {
      console.log(e.stdout?.toString());
      console.log(e.stderr?.toString());
      throw e;
    }
  }

  private async callJson<R>(args: string[]): Promise<R> {
    const output = await this.call(args);

    // the entrypoint prints "Using WordPress install <folder>." before the command output,
    // so the JSON has to be located within the output first.
    const startOfJson = output.search(/[[{]/);
    if (startOfJson === -1) {
      throw new Error(`could not find JSON in wp-cli output:\n${output}`);
    }

    return JSON.parse(output.substring(startOfJson)) as R;
  }

  async pluginList(): Promise<PluginListEntry[]> {
    return this.callJson<PluginListEntry[]>(['plugin', 'list', '--format=json', '--fields=name,status,version']);
  }

  async installPlugins(pathsToZips: string[], options: InstallPluginOptions = {}) {
    if (!pathsToZips.length) {
      return;
    }

    const args = ['plugin', 'install', ...pathsToZips.map((p) => this.toContainerPath(p))];
    if (options.force) {
      args.push('--force');
    }
    if (options.activate) {
      args.push('--activate');
    }

    await this.call(args);
  }

  async activatePlugins(slugs: string[]) {
    if (!slugs.length) {
      return;
    }

    await this.call(['plugin', 'activate', ...slugs]);
  }

  async updateOption(name: string, value: string) {
    await this.call(['option', 'update', name, value]);
  }

  async matomoUpdate() {
    await this.call(['matomo', 'update']);
  }

  async evalFile(pathToScript: string, user?: string) {
    const args = ['eval-file', this.toContainerPath(pathToScript)];
    if (user) {
      args.push(`--user=${user}`);
    }

    return this.call(args);
  }
}

export default new WpCli();
