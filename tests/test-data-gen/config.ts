/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import * as path from 'node:path';
import * as fs from 'node:fs';
import { fileURLToPath } from 'node:url';
import minimist from 'minimist';
import { Product } from '../e2e/pageobjects/blog-product.page.js';
import { Post } from '../e2e/pageobjects/blog-post.page.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// random IPs
const IP_ADDRESSES = [
  '194.57.91.0',
  '137.82.130.0',
  '113.62.1.0',
  '151.100.101.0',
  '103.29.196.0',
  '14.56.89.0',
  '158.98.37.0',
  '26.90.7.0',
  '93.20.17.0',
  '86.23.99.0',
  '145.112.230.0',
  '190.101.28.0',
  '89.23.45.0',
  '100.12.13.0',
  '46.229.33.0',
  '199.23.44.0',
  '68.92.48.0',
  '40.34.74.0',
  '99.23.46.0',
  '172.45.91.0',
];

// random user agents
const USER_AGENTS = [
  'Mozilla/5.0 (Linux; Android 4.4.2; Nexus 4 Build/KOT49H) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.136 Mobile Safari/537.36',
  'Mozilla/5.0 (Linux; U; Android 2.3.7; fr-fr; HTC Desire Build/GRI40; MildWild CM-8.0 JG Stable) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1',
  'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36',
  'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; GTB6.3; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; OfficeLiveConnector.1.4; OfficeLivePatch.1.3)',
  'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; MDDSJS; rv:11.0) like Gecko',
  'Mozilla/5.0 (Linux; Android 4.1.1; SGPT13 Build/TJDS0170) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.114 Safari/537.36',
  'Mozilla/5.0 (Linux; U; Android 4.3; zh-cn; SM-N9006 Build/JSS15J) AppleWebKit/537.36 (KHTML, like Gecko)Version/4.0 MQQBrowser/5.0 Mobile Safari/537.36',
  'Mozilla/5.0 (X11; U; Linux i686; ru; rv:1.9.0.14) Gecko/2009090216 Ubuntu/9.04 (jaunty) Firefox/3.0.14',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.10 Safari/605.1.1',
  'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Mobile Safari/537.3',
  'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:136.0) Gecko/20100101 Firefox/136.0',
  'Mozilla/5.0 (Windows NT 10.0; WOW64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/118.0.0.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.7; rv:128.0) Gecko/20100101 Firefox/128.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_7_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 OPR/118.0.0.0',
  'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36',
  'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:136.0) Gecko/20100101 Firefox/136.0',
  'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
  'Mozilla/5.0 (iPhone; CPU iPhone OS 17_7_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 EdgiOS/134.3124.77 Mobile/15E148 Safari/605.1.15',
  'Mozilla/5.0 (iPad; CPU OS 14_7_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/136.0 Mobile/15E148 Safari/605.1.15',
];

function* randomElements<T>(values: T[]): Generator<T> {
  while (true) {
    const n = Math.floor(Math.random() * values.length);
    yield values[n];
  }
}

function* sequentialElements<T>(values: T[]): Generator<T> {
  let i = 0;
  while (true) {
    yield values[i];
    i = (i + 1) % values.length;
  }
}

function* addLastByteToIp(wrap: Generator<string>): Generator<string> {
  let n = 0;
  for (const ip of wrap) {
    let parts = ip.split('.');
    parts[parts.length - 1] = n.toString();

    yield parts.join('.');

    n = (n + 1) % 255;
  }
}

interface VisitorInterface {
  execute(): Promise<void>;
}

function getAllVisitorTypes(): string[] {
  const visitorTypeDir = path.join(__dirname, 'visitors');
  return fs.readdirSync(visitorTypeDir)
    .filter(d => d !== '.' && d !== '..' && !/^index/.test(d))
    .map(f => f.split('.').slice(0, -1).join('.'));
}

async function* instantiateVisitors(visitorNames: Generator<string>): AsyncGenerator<VisitorInterface> {
  for (let v of visitorNames) {
    const p = path.join(__dirname, 'visitors', `${v}.js`);
    const VisitorType = (await import(p)).default;

    yield new VisitorType();
  }
}
export class Config {
  public visits: number;
  public visitors: AsyncGenerator<VisitorInterface>;
  public ipAddresses: Generator<string>;
  public userAgents: Generator<string>;
  public products: Generator<Product>;
  public posts: Generator<Post>;
  public verbose: boolean;

  constructor(config: Record<string, string> = {}) {
    this.visits = this.getVisitsConfig(config);
    this.visitors = this.getVisitorsConfig(config);
    this.ipAddresses = this.getIpAddressesConfig(config);
    this.userAgents = this.getUserAgentsConfig(config);
    this.products = this.getProductsConfig(config);
    this.posts = this.getPostsConfig(config);
    this.verbose = !! config.verbose;
  }

  getVisitsConfig(config: Record<string, string>): number {
    const visitsConfig = config.visits || process.env.MWP_TEST_DATA_VISITS_COUNT || '100';
    if (visitsConfig === 'random') {
      return Math.floor(Math.random() * 1000) + 1;
    }

    const visitsConfigNum = parseInt(visitsConfig, 10);
    if (!visitsConfigNum) {
      throw new Error(`Invalid config for visits count. Expected integer or "random", received "${visitsConfig}".`);
    }

    return visitsConfigNum;
  }

  getVisitorsConfig(config: Record<string, string>): AsyncGenerator<VisitorInterface> {
    const allVisitorTypes = getAllVisitorTypes();

    const series = this.getSeriesConfig(
      'visitors',
      config.visitors || process.env.MWP_VISITORS_SERIES_TYPE || 'sequential',
      allVisitorTypes
    );

    return instantiateVisitors(series);
  }

  getIpAddressesConfig(config: Record<string, string>): Generator<string> {
    const series = this.getSeriesConfig(
      'ip_addresses',
      config.ip_addresses || process.env.MWP_IP_ADDRESSES_SERIES_TYPE || 'sequential',
      IP_ADDRESSES
    );
    return addLastByteToIp(series);
  }

  getUserAgentsConfig(config: Record<string, string>): Generator<string> {
    return this.getSeriesConfig(
      'user_agents',
      config.user_agents || process.env.MWP_USER_AGENTS_SERIES_TYPE || 'sequential',
      USER_AGENTS
    );
  }

  getProductsConfig(config: Record<string, string>): Generator<Product> {
    return this.getSeriesConfig(
      'products',
      config.products || process.env.MWP_PRODUCTS_SERIES_TYPE || 'sequential',
      Object.values(Product),
    );
  }

  getPostsConfig(config: Record<string, string>): Generator<Post> {
    return this.getSeriesConfig(
      'posts',
      config.posts || process.env.MWP_POSTS_SERIES_TYPE || 'sequential',
      Object.values(Post)
    );
  }

  getSeriesConfig<T>(configName: string, seriesType: string, values: T[]): Generator<T> {
    if (seriesType === 'random') {
      return randomElements(values);
    }

    if (seriesType === 'sequential') {
      return sequentialElements(values);
    }
    throw new Error(`Invalid config found for ${configName}. Expected 'random' or 'sequential', found: '${seriesType}'.`);
  }

  static getConfigFromCliArgs(): Record<string, string> {
    return minimist(process.argv.slice(2));
  }
}

export default new Config(Config.getConfigFromCliArgs());
