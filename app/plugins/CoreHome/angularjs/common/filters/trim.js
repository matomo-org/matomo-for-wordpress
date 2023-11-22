/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp.filter').filter('trim', trim);

    function trim() {

        return function(string) {
            if (string) {
                return $.trim('' + string);
            }

            return string;
        };
    }
})();
