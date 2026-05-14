/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoReportingPage from '../matomo-reporting.page.js';
import DashboardPage from "./dashboard.page";

const promos = {
  Funnels: 'ProfessionalServices_PromoFunnels.ProfessionalServices_PromoOverview',
  Heatmaps: 'ProfessionalServices_PromoHeatmaps.ProfessionalServices_PromoManage',
  SessionRecording: 'ProfessionalServices_PromoSessionRecording.ProfessionalServices_PromoManage',
  CrashAnalytics: 'ProfessionalServices_PromoCrashAnalytics.ProfessionalServices_PromoOverview',
  CustomReports: 'ProfessionalServices_PromoCustomReports.ProfessionalServices_PromoManage',
}

class PromoPage extends MatomoReportingPage {
  async open(pluginName: string) {
    await super.open(promos[pluginName], { force_promo: '1' });
  }

  async dismiss() {
    await browser.execute(() => {
      $('.promo-dismiss > a')[0].click();
    });

    await DashboardPage.waitForDashboard();

    // hide dashboard, we don't need to test this content
    await this.addStylesToPage(`#dashboardWidgetsArea { display: none !important; }`);
  }
}

export default new PromoPage();
