/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

console.log('plugins-admin');
// TODO: make this an inline script
jQuery(document).ready(
  function () {
    var $title = jQuery('body.plugins-php tr[data-slug="matomo"] td.plugin-title > strong:first-child');
    $title.after('<p><em>Data will be deleted upon plugin deletion.</em> <a href="">Change this.</a></p>');
  }
);
