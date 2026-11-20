/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

// Keep reviewed screenshot fixtures separate where matrix environments differ.
export function getScreenshotName(name: string): string {
  const phpVersion = process.env.PHP_VERSION;
  const wordpressVersion = process.env.WORDPRESS_VERSION;

  if (phpVersion === '8.1' && (wordpressVersion === 'latest' || wordpressVersion === 'trunk')) {
    return `${name}.php81`;
  }

  return phpVersion === '7.4' && wordpressVersion === 'latest'
    ? `${name}.php74-latest`
    : name;
}

// Only the PHP 8.1 install modal needs separate latest/trunk fixtures.
export function getInstallCodeScreenshotName(): string {
  const name = 'matomo.tag-manager.install-code-modal';
  const wordpressVersion = process.env.WORDPRESS_VERSION;
  return process.env.PHP_VERSION === '8.1'
    && (wordpressVersion === 'latest' || wordpressVersion === 'trunk')
    ? `${name}.php81-${wordpressVersion}`
    : name;
}
