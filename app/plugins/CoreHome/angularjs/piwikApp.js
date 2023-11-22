/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
(function () {
    angular.module('piwikApp', [
        'ngSanitize',
        'ngAnimate',
        'ngCookies',
        'piwikApp.config',
        'piwikApp.service',
        'piwikApp.directive',
        'piwikApp.filter'
    ]);
    angular.module('app', []);

    angular.module('piwikApp').config(['$locationProvider', function($locationProvider) {
        $locationProvider.html5Mode({ enabled: false, rewriteLinks: false }).hashPrefix('');
    }]);
})();