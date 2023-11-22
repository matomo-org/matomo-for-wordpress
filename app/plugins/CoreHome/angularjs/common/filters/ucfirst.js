/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp.filter').filter('ucfirst', ucfirst);

    function ucfirst() {

        return function(value) {
            if (!value) {
                return value;
            }

            var firstLetter = (value + '').charAt(0).toUpperCase();
            return firstLetter + value.slice(1);
        };
    }
})();
