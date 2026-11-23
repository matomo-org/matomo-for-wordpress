/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

// Only reviewed PHP 7.4/trunk differences use these fixtures.
const php74TrunkScreenshots = new Set([
  'matomo-admin.privacy.users-opt-out.7.4',
  'matomo-reporting.acquisition.overview',
  'matomo-reporting.ai-assistants.ai-agents-overview',
  'matomo-reporting.ai-assistants.ai-chatbots-overview',
  'matomo-reporting.behaviour.contents',
  'matomo-reporting.behaviour.engagement',
  'matomo-reporting.behaviour.performance',
  'matomo-reporting.dashboard',
  'matomo-reporting.ecommerce.overview',
  'matomo-reporting.goals.overview',
  'matomo-reporting.visitors.overview',
  'matomo.tag-manager.install-code-modal',
  'matomo.tag-manager.preview-mode-enabled',
  'matomo.tag-manager.publish-modal',
  'mwp-admin.diagnostics.system-report.7.4.trunk',
  'mwp-admin.multisite.mtm-reporting',
  'mwp-admin.network.diagnostics.7.4.trunk',
  'mwp.page-with-opt-out.7.4.trunk',
  'mwp.page-with-opt-out.classic-changed.7.4.trunk',
]);

export function getPhp74TrunkScreenshotName(name: string): string {
  return process.env.PHP_VERSION === '7.4'
    && process.env.WORDPRESS_VERSION === 'trunk'
    && php74TrunkScreenshots.has(name)
    ? `${name}.php74-trunk`
    : name;
}

// Keep reviewed screenshot fixtures separate where matrix environments differ.
export function getScreenshotName(name: string): string {
  const trunkName = getPhp74TrunkScreenshotName(name);
  if (trunkName !== name) {
    return trunkName;
  }

  const phpVersion = process.env.PHP_VERSION;
  const wordpressVersion = process.env.WORDPRESS_VERSION;

  if (phpVersion === '8.1' && (wordpressVersion === 'latest' || wordpressVersion === 'trunk')) {
    return `${name}.php81`;
  }

  return phpVersion === '7.4' && wordpressVersion === 'latest'
    ? `${name}.php74-latest`
    : name;
}

// The install modal has its own PHP 8.1 latest/trunk fixtures.
export function getInstallCodeScreenshotName(): string {
  const name = 'matomo.tag-manager.install-code-modal';
  const wordpressVersion = process.env.WORDPRESS_VERSION;
  return process.env.PHP_VERSION === '8.1'
    && (wordpressVersion === 'latest' || wordpressVersion === 'trunk')
    ? `${name}.php81-${wordpressVersion}`
    : getPhp74TrunkScreenshotName(name);
}
