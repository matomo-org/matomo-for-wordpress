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

const DOWNLOADS_DIR = path.join(dirname, 'downloads');

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

interface MarketplacePlugin {
  name: string;
  displayName?: string;
  owner?: string;
  isDownloadable?: boolean;
  downloadUrl?: string;
}

function log(message: string) {
  console.log(`[premium-plugins] ${message}`);
}

/**
 * Installs the Matomo marketplace plugins that the tests expect to be present.
 *
 * The tracking wdio run does this through the browser in tests/e2e/mwp-admin.marketplace.e2e.ts,
 * which is the test of that flow. This exists so the main wdio run does not depend on that run
 * having succeeded. When it succeeds, everything here is already in place and this is a no-op.
 */
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
      // the marketplace spec does this by opening a Matomo page afterwards, which triggers
      // any pending plugin component updates
      await WpCli.matomoUpdate();
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
      if (!(parsed instanceof Array)) {
        throw new Error(`expected a list of marketplace plugins, got: ${json.substring(0, 500)}`);
      }

      return parsed as MarketplacePlugin[];
    }, 2000) as MarketplacePlugin[];
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

    // the marketplace names the download after the version, so it has to be renamed to keep
    // downloads from colliding. Requests to the endpoint have to be POSTs with the access
    // token in the body, which is what MatomoMarketplaceAdmin::add_authentication_if_needed()
    // arranges for WordPress. The environment parameters are already in the URL.
    const pathToZip = path.join(DOWNLOADS_DIR, `${plugin.name}.zip`);

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

      fs.writeFileSync(pathToZip, contents);
    }, 2000);

    return pathToZip;
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
