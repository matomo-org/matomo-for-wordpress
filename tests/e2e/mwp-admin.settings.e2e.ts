/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import {expect, browser, $} from '@wdio/globals';
import MwpSettingsPage from './pageobjects/mwp-admin/settings.page.js';
import Website from './website.js';

describe('MWP Admin > Settings', () => {
  const trunkSuffix = process.env.WORDPRESS_VERSION === 'trunk' ? '.trunk' : '';

  before(async () => {
    if (!process.env.PHP_VERSION) {
      throw new Error('Unexpected: PHP_VERSION environment variable cannot be found.');
    }

    await Website.login();
  });

  it('should load the tracking tab correctly', async () => {
    await MwpSettingsPage.open();

    // sometimes it loads deselected
    await browser.execute(() => {
      window.jQuery('#track_mode_default')[0].click();
    });

    await MwpSettingsPage.removeTagManagerContainerIds();
    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.tracking.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should expand all tracking settings sections correctly', async () => {
    await MwpSettingsPage.undoChangesToWpAdminForScreenshot();

    await MwpSettingsPage.expandAllTrackingSettingsSections();

    await MwpSettingsPage.removeTagManagerContainerIds();
    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.tracking.all-expanded.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should update the generated tracking code when an Auto Tracking mode setting changes', async () => {
    await MwpSettingsPage.undoChangesToWpAdminForScreenshot();

    await MwpSettingsPage.selectTrackMode('default');
    await MwpSettingsPage.changeSomeAutoTrackingSettings();

    await MwpSettingsPage.removeTagManagerContainerIds();
    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.tracking.auto-changed.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should correctly save tracking settings when the submit button is clicked', async () => {
    await MwpSettingsPage.undoChangesToWpAdminForScreenshot();

    await MwpSettingsPage.saveSettings();
    await MwpSettingsPage.expandAllTrackingSettingsSections();

    await MwpSettingsPage.removeTagManagerContainerIds();
    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.tracking.saved.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the access tab correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openAccessTab();

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.access.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the privacy tab correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openPrivacyTab();

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.privacy.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the exclusions tab correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openExclusionsTab();

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.exclusions.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the geolocation tab correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openGeolocationTab();

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.geolocation.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load the advanced tab correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openAdvancedTab();

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.advanced.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should load plugin measurable settings correctly', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openMeasurableSettings('SEO Web Vitals');

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.seowebvitals.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should save measurable setting values correctly', async () => {
    await MwpSettingsPage.setSeoWebVitalsSettingValue('http://somesite.com');
    await browser.refresh();
    await $('iframe').waitForDisplayed();
    await browser.pause(2000); // wait for iframe resizer to activate

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.seowebvitals-saved.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });

  it('should display the appropriate message when measurable settings are hidden', async () => {
    await MwpSettingsPage.open();
    await MwpSettingsPage.openMeasurableSettings('Search Engine Keywords Performance');

    await MwpSettingsPage.prepareWpAdminForScreenshot();
    await expect(
      await browser.checkFullPageScreen(`mwp-admin.settings.searchperformance.${process.env.PHP_VERSION}${trunkSuffix}`)
    ).toEqual(0);
  });
});
