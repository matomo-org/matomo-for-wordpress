/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'node:path';
import { writeFile , readFile } from 'node:fs/promises'
import { stringify , parse } from 'ini'
import Website from '../website.js'

type IniObject = Awaited<ReturnType<parse>>;

class MatomoIniConfig {
  async set(section: string, key: string, value: any) {
    const iniFile = await this.getConfigIniContents();
    iniFile[section] = iniFile[section] || {};
    iniFile[section][key] = value;
    await this.writeConfigIniContents(iniFile);
  }

  async remove(section: string, key: string) {
    const iniFile = await this.getConfigIniContents();
    if (iniFile[section]) {
      delete iniFile[section][key];
    }
    await this.writeConfigIniContents(iniFile);
  }

  async writeConfigIniContents(iniFile: IniObject) {
    const configIniPath = await this.getConfigIniPath();
    const text = stringify(iniFile);
    await writeFile(configIniPath, text);
  }

  async getConfigIniContents(): Promise<IniObject> {
    const configIniPath = await this.getConfigIniPath();
    const text = await readFile(configIniPath, { encoding : 'utf-8' })
    return parse(text);
  }

  async getConfigIniPath(): Promise<string> {
    return path.join(process.cwd(), 'docker', 'wordpress', await Website.getWpFolder(), 'wp-content', 'uploads', 'matomo', 'config', 'config.ini.php');
  }
}

export default new MatomoIniConfig();
