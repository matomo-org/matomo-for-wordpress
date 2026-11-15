/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { $, browser } from '@wdio/globals';
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

    for (let attempt = 0; attempt < 3; ++attempt) {
      await super.open(promos[promoName], { force_promo: '1' });

      // Promo content comes from the live Marketplace. A failed widget request
      // renders an error panel instead of .pluginPromo and will not recover by waiting.
      await browser.waitUntil(async () => {
        return await $('.pluginPromo').isDisplayed()
          || await browser.execute(() => /there was a problem during the request/i.test(document.body.innerText));
      }, { timeout: 30000 });

      if (await $('.pluginPromo').isDisplayed()) {
        return;
      }

      if (attempt === 2) {
        throw new Error(`Could not load ${promoName} promo: the request failed on all 3 attempts`);
      }

      console.log(`Retrying ${promoName} promo after a request error (attempt ${attempt + 2}/3)`);
      await browser.pause(1000 * (attempt + 1));
    }
  }

  async dismiss() {
    await browser.execute(() => {
      $('.promo-dismiss > a')[0].click();
    });

    await browser.waitUntil(() => {
      return browser.execute(() => {
        return $('.notification-body:contains(menu will no longer be shown)').length > 0;
      })
    });
  }
}

export default new PromoPage();
