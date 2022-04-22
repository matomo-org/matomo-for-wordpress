/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import DashboardStore from './Dashboard.store';

window.angular.module('piwikApp.service').factory('dashboardsModel', () => DashboardStore);
