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
  MediaAnalytics: 'ProfessionalServices_PromoMediaAnalytics.ProfessionalServices_PromoOverview',
  FormAnalytics: 'ProfessionalServices_PromoFormAnalytics.ProfessionalServices_PromoOverview',
}

class PromoPage extends MatomoReportingPage {
  async open(promoName: string) {
    if (!promos[promoName]) {
      throw new Error(`unknown promo: ${promoName}`);
    }

    await super.open(promos[promoName], { force_promo: '1' });
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
