/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'path';
import * as url from 'url';
import WpCli from './apiobjects/wp.cli.js';

const dirname = path.dirname(url.fileURLToPath(import.meta.url));

const SETUP_SCRIPT = path.join(dirname, 'resources', 'woocommerce-setup.php');

class WooCommerceSetup {
  private isSetUp = false;

  async setUp() {
    if (this.isSetUp) {
      return;
    }

    const output = await WpCli.evalFile(SETUP_SCRIPT, process.env.WP_ADMIN_USER || 'root');
    console.log(`[woocommerce-setup] ${output.trim()}`);

    this.isSetUp = true;
  }

  async setUpIfInstalled() {
    const plugins = await WpCli.pluginList();
    if (!plugins.some((plugin) => plugin.name === 'woocommerce' && plugin.status === 'active')) {
      console.log('[woocommerce-setup] WooCommerce is not active, not setting it up.');
      return;
    }

    await this.setUp();
  }
}

export default new WooCommerceSetup();
