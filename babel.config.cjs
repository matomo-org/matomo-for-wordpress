/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

// babel config for Matomo for WordPress post-processing of core Matomo JS,
// so we can directly embed the Matomo UI in WordPress.
module.exports = {
  plugins: [
    ['./scripts/babel/post-process-matomo-script'],
  ],
};
