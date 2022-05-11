/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp.filter').filter('length', length);

    function length() {

        return function(stringOrArray) {
            if (stringOrArray && stringOrArray.length) {
                return stringOrArray.length;
            }

            return 0;
        };
    }

})();