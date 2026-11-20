/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

// The latest/PHP 7.4 job uses different GD rendering and compatible plugin versions.
// Keep its reviewed snapshots separate from all other matrix jobs.
export function getScreenshotName(name: string): string {
  return process.env.PHP_VERSION === '7.4' && process.env.WORDPRESS_VERSION === 'latest'
    ? `${name}.php74-latest`
    : name;
}
