/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'node:path';
import { writeFile, readFile, rename, stat, chmod } from 'node:fs/promises';
import Website from '../website.js'

class MatomoIniConfig {
  async set(section: string, key: string, value: any) {
    await this.updateConfigIni(section, key, value);
  }

  async remove(section: string, key: string) {
    await this.updateConfigIni(section, key);
  }

  private async updateConfigIni(section: string, key: string, value?: any) {
    key = key.replace(/\[\]$/, '');
    if (!/^[a-zA-Z0-9_]+$/.test(section) || !/^[a-zA-Z0-9_]+$/.test(key)) {
      throw new Error('Unsupported INI section or key');
    }

    const configIniPath = await this.getConfigIniPath();
    const text = await readFile(configIniPath, 'utf-8');
    const lines = text.split('\n');
    const start = lines.findIndex((line) => line.trim() === `[${section}]`);
    const entries: string[] = [];
    if (value !== undefined) {
      const values = Array.isArray(value) ? value : [value];
      for (const item of values) {
        // Quote strings for PHP's INI parser, including parentheses and backslashes.
        const encoded = typeof item === 'string'
          ? `"${item.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`
          : typeof item === 'boolean' ? Number(item) : item;
        entries.push(`${key}${Array.isArray(value) ? '[]' : ''} = ${encoded}`);
      }
    }

    // Preserve every unrelated line, including PHP guards and quoted PHP INI values.
    // The npm ini serializer can remove quotes that PHP requires, corrupting the file.
    if (start === -1) {
      if (value === undefined) {
        return;
      }
      lines.push('', `[${section}]`, ...entries, '');
    } else {
      let end = start + 1;
      while (end < lines.length && !/^\s*\[/.test(lines[end])) {
        ++end;
      }
      const keyPattern = new RegExp(`^\\s*${key}(?:\\[[^\\]]*\\])?\\s*=`);
      const sectionLines = lines.slice(start + 1, end).filter((line) => !keyPattern.test(line));
      lines.splice(start + 1, end - start - 1, ...entries, ...sectionLines);
    }

    // Publish the complete file at once so web requests cannot read a partial write.
    const temporaryPath = `${configIniPath}.e2e-${process.pid}.tmp`;
    const { mode } = await stat(configIniPath);
    await writeFile(temporaryPath, lines.join('\n'), { mode });
    await chmod(temporaryPath, mode);
    await rename(temporaryPath, configIniPath);
  }

  async getConfigIniPath(): Promise<string> {
    return path.join(process.cwd(), 'docker', 'wordpress', await Website.getWpFolder(), 'wp-content', 'uploads', 'matomo', 'config', 'config.ini.php');
  }
}

export default new MatomoIniConfig();
