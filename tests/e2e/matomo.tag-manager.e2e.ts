/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import GettingStartedPage from './pageobjects/tag-manager/getting-started.page.js';
import ManageContainersPage from './pageobjects/tag-manager/manage-containers.page.js';
import ContainerDashboardPage from './pageobjects/tag-manager/container-dashboard.page.js';
import ContainerTagsPage from './pageobjects/tag-manager/container-tags.page.js';
import ContainerVariablesPage from './pageobjects/tag-manager/container-variables.page.js';
import ContainerVersionsPage from './pageobjects/tag-manager/container-versions.page.js';
import ContainerTriggersPage from './pageobjects/tag-manager/container-triggers.page.js';
import Website from './website.js';

describe('Matomo > Tag Manager', () => {
  before(async () => {
    await Website.login();
  });

  it('should load the getting started page correctly', async () => {
    await GettingStartedPage.open();

    await GettingStartedPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.getting-started')
    ).toEqual(0);
  });

  it('should load the manage containers page correctly', async () => {
    await ManageContainersPage.open();

    await ManageContainersPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.manage-containers')
    ).toEqual(0);
  });

  it('should load the container dashboard page correctly', async () => {
    await ContainerDashboardPage.open();

    await ContainerDashboardPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.container-dashboard')
    ).toEqual(0);
  });

  it('should load the container tags page correctly', async () => {
    await ContainerTagsPage.open();

    await ContainerTagsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.container-tags')
    ).toEqual(0);
  });

  it('should load the container variables page correctly', async () => {
    await ContainerVariablesPage.open();

    await ContainerVariablesPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.container-variables')
    ).toEqual(0);
  });

  it('should load the container versions page correctly', async () => {
    await ContainerVersionsPage.open();

    await ContainerVersionsPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.container-versions')
    ).toEqual(0);
  });

  it('should load the container triggers page correctly', async () => {
    await ContainerTriggersPage.open();

    await ContainerTriggersPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.container-triggers')
    ).toEqual(0);
  });

  it('should load the publish popup correctly', async () => {
    await ContainerTriggersPage.open();

    await ContainerTriggersPage.openPublishModal();

    await ContainerTriggersPage.disableModalScroll();
    await ContainerTriggersPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.publish-modal')
    ).toEqual(0);
  });

  it('should load the install code popup page correctly', async () => {
    await ContainerTriggersPage.open();

    await ContainerTriggersPage.openInstallCodeModal();

    await ContainerTriggersPage.disableModalScroll();
    await ContainerTriggersPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.install-code-modal')
    ).toEqual(0);
  });

  it('should enable preview mode correctly', async () => {
    await ContainerTriggersPage.open();

    await ContainerTriggersPage.enablePreviewMode();

    await ContainerTriggersPage.disableHoverStyles();
    await expect(
      await browser.checkFullPageScreen('matomo.tag-manager.preview-mode-enabled')
    ).toEqual(0);
  });
});
