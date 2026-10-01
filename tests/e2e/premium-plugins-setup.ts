/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import fetch from 'node-fetch';
import * as path from 'path';
import * as fs from 'fs';
import * as url from 'url';
import MatomoCli from './apiobjects/matomo.cli.js';
import WpCli from './apiobjects/wp.cli.js';
import Website from './website.js';

const dirname = path.dirname(url.fileURLToPath(import.meta.url));

// kept alongside wp-cli's download cache so CI can cache both as one folder
const DOWNLOADS_DIR = path.join(dirname, '..', '..', 'docker', 'wp-cli', 'marketplace');

if (!fs.existsSync(DOWNLOADS_DIR)) {
  fs.mkdirSync(DOWNLOADS_DIR, { recursive: true });
}

const AVAILABLE_PLUGINS_SCRIPT = path.join(dirname, 'resources', 'marketplace-available-plugins.php');
const AVAILABLE_PLUGINS_JSON_MARKER = 'MARKETPLACE_PLUGINS_JSON:';

const MARKETPLACE_WP_PLUGIN = 'matomo-marketplace-for-wordpress';
const LICENSE_OPTION = 'matomo_marketplace_license_key';

const MATOMO_ORG_OWNER = 'matomo-org';

// mirrors the filtering in MwpMarketplacePage.bulkInstallMatomoPlugins()
const EXCLUDED_SLUGS = ['ForceSSL'];

// plugins whose absence would only show up much later as screenshot diffs, so we fail fast
// instead if the marketplace stops offering them for our license
const EXPECTED_SLUGS = [
  'SearchEngineKeywordsPerformance',
  'Funnels',
  'HeatmapSessionRecording',
];

const DOWNLOAD_CONCURRENCY = 3;

// enough to wait out a short marketplace outage
const FETCH_ATTEMPTS = 5;
const FETCH_RETRY_DELAY_MS = 15000;

// other versions can still be in use, since test-tracking runs a different Matomo version
const OTHER_VERSIONS_MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000;

interface MarketplacePlugin {
  name: string;
  displayName?: string;
  owner?: string;
  isDownloadable?: boolean;
  latestVersion?: string;
  downloadUrl?: string;
}

function log(message: string) {
  console.log(`[premium-plugins] ${message}`);
}

// installs the marketplace plugins the main run expects, which the tracking run installs via the browser
// in its own WordPress install (mwp-admin.marketplace.e2e.ts)
class PremiumPluginsSetup {
  private isSetUp = false;

  async setUp() {
    if (this.isSetUp) {
      return;
    }

    this.isSetUp = true;

    const license = (process.env.TEST_SHOP_LICENSE || '').trim();
    if (!license) {
      log('TEST_SHOP_LICENSE is not set, marketplace plugins will not be installed.'
        + ' Screenshot tests that expect them to be present will fail.');
      return;
    }

    const pluginsDir = await this.pluginsDir();

    await this.ensureMarketplacePluginInstalled(pluginsDir);
    await WpCli.updateOption(LICENSE_OPTION, license);

    const available = await this.fetchAvailablePlugins();

    const toInstall = available.filter((plugin) => (
      plugin.owner === MATOMO_ORG_OWNER
      && !EXCLUDED_SLUGS.includes(plugin.name)
      && !this.isPluginInstalled(pluginsDir, plugin.name)
    ));

    this.checkExpectedPluginsArePresent(pluginsDir, toInstall);

    if (toInstall.length) {
      log(`installing ${toInstall.length} plugin(s): ${toInstall.map((p) => p.name).join(', ')}`);

      const pathsToZips = await this.downloadPlugins(toInstall, license);

      await WpCli.installPlugins(pathsToZips, { activate: true });

      toInstall.forEach((plugin) => this.checkPluginWasInstalled(pluginsDir, plugin.name));
    } else {
      log('all marketplace plugins are already installed.');
    }

    const activated = await this.activateInstalledPlugins(pluginsDir);

    if (toInstall.length || activated.length) {
      await WpCli.matomoInstall(); // trigger plugin installation code
    }

    await this.disableHeadlessBlocking();
  }

  private async pluginsDir() {
    return path.join(process.cwd(), 'docker', 'wordpress', await Website.getWpFolder(), 'wp-content', 'plugins');
  }

  /**
   * The same check MatomoMarketplaceAdmin::search_plugins() uses for `isInstalled`.
   */
  private isPluginInstalled(pluginsDir: string, slug: string) {
    return fs.existsSync(path.join(pluginsDir, slug, `${slug}.php`));
  }

  /**
   * The environment is set up with WITHOUT_MARKETPLACE=1, so the marketplace plugin is only
   * present once the marketplace spec has installed it. The main run needs it too, both to
   * store the license and because it shows up in screenshots.
   */
  private async ensureMarketplacePluginInstalled(pluginsDir: string) {
    if (this.isPluginInstalled(pluginsDir, MARKETPLACE_WP_PLUGIN)) {
      return;
    }

    log('the marketplace plugin is not installed, installing it now...');

    let pathToZip = path.join(process.cwd(), 'marketplace', `${MARKETPLACE_WP_PLUGIN}.test.zip`);
    if (!fs.existsSync(pathToZip)) {
      pathToZip = MatomoCli.buildMarketplaceRelease();
    }

    await WpCli.installPlugins([pathToZip], { activate: true, force: true });

    this.checkPluginWasInstalled(pluginsDir, MARKETPLACE_WP_PLUGIN, false);
  }

  /**
   * Asks the marketplace plugin itself for the plugins available to the configured license,
   * rather than reimplementing MatomoMarketplaceApi in here. That way the environment
   * parameters, the compatibility filtering and the download URLs cannot drift from what the
   * marketplace spec ends up installing through the browser.
   */
  private async fetchAvailablePlugins() {
    return await Website.retry(3, async () => {
      const output = await WpCli.evalFile(AVAILABLE_PLUGINS_SCRIPT);

      const startOfJson = output.indexOf(AVAILABLE_PLUGINS_JSON_MARKER);
      if (startOfJson === -1) {
        throw new Error(`could not read the available marketplace plugins:\n${output}`);
      }

      const json = output.substring(startOfJson + AVAILABLE_PLUGINS_JSON_MARKER.length);

      const parsed = JSON.parse(json);
      if (!(parsed instanceof Array) || !parsed.length) {
        throw new Error(`expected a list of marketplace plugins, got: ${json.substring(0, 500)}`);
      }

      return parsed as MarketplacePlugin[];
    }, FETCH_ATTEMPTS, FETCH_RETRY_DELAY_MS) as MarketplacePlugin[];
  }

  private checkExpectedPluginsArePresent(pluginsDir: string, toInstall: MarketplacePlugin[]) {
    const missing = EXPECTED_SLUGS.filter((slug) => (
      !this.isPluginInstalled(pluginsDir, slug)
      && !toInstall.some((plugin) => plugin.name === slug)
    ));

    if (missing.length) {
      throw new Error('The marketplace did not offer the following plugins for TEST_SHOP_LICENSE: '
        + `${missing.join(', ')}. The license most likely no longer covers them.`);
    }
  }

  private async downloadPlugins(plugins: MarketplacePlugin[], license: string) {
    const pathsToZips: string[] = [];
    const queue = [...plugins];

    const workers = Array.from({ length: Math.min(DOWNLOAD_CONCURRENCY, queue.length) }, async () => {
      for (let plugin = queue.shift(); plugin; plugin = queue.shift()) {
        pathsToZips.push(await this.downloadPlugin(plugin, license));
      }
    });

    await Promise.all(workers);

    return pathsToZips;
  }

  private async downloadPlugin(plugin: MarketplacePlugin, license: string) {
    if (!plugin.isDownloadable || !plugin.downloadUrl) {
      throw new Error(`The marketplace does not consider ${plugin.name} downloadable with the`
        + ' license in TEST_SHOP_LICENSE, so it cannot be installed.');
    }

    const version = this.getDownloadVersion(plugin);
    const pathToZip = path.join(DOWNLOADS_DIR, `${plugin.name}-${version}.zip`);

    if (fs.existsSync(pathToZip)) {
      log(`using cached ${path.basename(pathToZip)}`);
      return pathToZip;
    }

    await Website.retry(3, async () => {
      const response = await fetch(plugin.downloadUrl!, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ access_token: license }).toString(),
      });

      if (!response.ok) {
        throw new Error(`could not download ${plugin.name}: HTTP ${response.status}`);
      }

      const contents = Buffer.from(await response.arrayBuffer());

      // the marketplace responds with JSON when it refuses the request
      if (contents.subarray(0, 2).toString('latin1') !== 'PK') {
        throw new Error(`could not download ${plugin.name}: ${contents.toString('utf-8').substring(0, 500)}`);
      }

      // written under a temporary name so an interrupted run cannot leave a partial zip in the cache.
      // unique, since the e2e runs can download the same zip concurrently.
      const tmpPath = `${pathToZip}.${process.pid}.tmp`;
      fs.writeFileSync(tmpPath, contents);
      fs.renameSync(tmpPath, pathToZip);
    }, 2000);

    this.removeStaleCachedVersions(plugin.name, pathToZip);

    return pathToZip;
  }

  private getDownloadVersion(plugin: MarketplacePlugin) {
    const version = path.posix.basename(new URL(plugin.downloadUrl!).pathname);
    if (/^\d+\.\d+/.test(version)) {
      return version;
    }

    if (!plugin.latestVersion) {
      throw new Error(`could not determine the version of ${plugin.name} from ${plugin.downloadUrl}`);
    }

    return plugin.latestVersion;
  }

  private removeStaleCachedVersions(slug: string, pathToKeep: string) {
    fs.readdirSync(DOWNLOADS_DIR)
      .filter((file) => file.startsWith(`${slug}-`) && file.endsWith('.zip'))
      .map((file) => path.join(DOWNLOADS_DIR, file))
      .filter((file) => file !== pathToKeep)
      .filter((file) => Date.now() - fs.statSync(file).mtimeMs > OTHER_VERSIONS_MAX_AGE_MS)
      .forEach((file) => fs.rmSync(file, { force: true }));
  }

  /**
   * Both TGMPA and Matomo core rename the folder in a plugin zip when it does not match the
   * plugin name, but `wp plugin install` does not, so make sure the result is what the rest of
   * the tests expect to find.
   */
  private checkPluginWasInstalled(pluginsDir: string, slug: string, expectPluginJson: boolean = true) {
    if (!this.isPluginInstalled(pluginsDir, slug)) {
      throw new Error(`installed ${slug}, but ${slug}/${slug}.php does not exist. The zip most likely`
        + ' does not have a single root folder named after the plugin.');
    }

    // mwp-admin.marketplace.e2e.ts uses plugin.json to find the plugins it has to clean up
    if (expectPluginJson && !fs.existsSync(path.join(pluginsDir, slug, 'plugin.json'))) {
      throw new Error(`installed ${slug}, but ${slug}/plugin.json does not exist.`);
    }
  }

  /**
   * Activates any marketplace plugin that is present but not active, so that a marketplace spec
   * that failed between installing and activating does not leave the main run broken.
   */
  private async activateInstalledPlugins(pluginsDir: string) {
    // marketplace plugins are identified by their plugin.json, same as the marketplace spec
    // does when it cleans them up. The marketplace plugin itself does not have one.
    const installed = fs.readdirSync(pluginsDir).filter((dir) => (
      fs.existsSync(path.join(pluginsDir, dir, 'plugin.json'))
    ));

    if (this.isPluginInstalled(pluginsDir, MARKETPLACE_WP_PLUGIN)) {
      installed.push(MARKETPLACE_WP_PLUGIN);
    }

    const plugins = await WpCli.pluginList();

    const inactive = installed.filter((slug) => (
      plugins.find((plugin) => plugin.name === slug)?.status !== 'active'
    ));

    if (inactive.length) {
      log(`activating ${inactive.length} inactive plugin(s): ${inactive.join(', ')}`);
      await WpCli.activatePlugins(inactive);
    }

    return inactive;
  }

  /**
   * Same as the end of MwpMarketplacePage.bulkActivateMatomoPlugins(): without this the
   * headless browser is treated as a bot and nothing is tracked.
   */
  private async disableHeadlessBlocking() {
    await fetch(`${await Website.baseUrl()}/wp-admin/admin-ajax.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'matomo_test_disable_block_headless',
      }).toString(),
    });
  }
}

export default new PremiumPluginsSetup();
