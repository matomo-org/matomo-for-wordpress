/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import MatomoApi from './apiobjects/matomo.api.js';
import MatomoCli from './apiobjects/matomo.cli.js';
import WpCli from './apiobjects/wp.cli.js';
import Website from './website.js';

// TODO: need to document how to write an e2e test. eg, individual tests
// should ideally not add data, data should be added beforehand in global
// setup, etc.
class GlobalSetup {
  private isSetUp = false;
  private _testIdGoal: number|null = null;
  private _testIdContainer: string|null = null;

  get testIdGoal(): number {
    if (!this._testIdGoal) {
      throw new Error('testIdGoal has not been set yet');
    }

    return this._testIdGoal;
  }

  get testIdContainer(): string {
    if (!this._testIdContainer) {
      throw new Error('testIdContainer has not been set yet');
    }

    return this._testIdContainer;
  }

  async setUp() {
    if (this.isSetUp) {
      return;
    }

    await WpCli.call(['matomo', 'sync', 'sites', '--verbose']);
    await this.createTestGoal();
    await this.addTagManagerEntities();

    if (!(await this.isVisitAlreadyTracked())) {
      await this.trackVisitsInPast();
      await this.trackOrderInPast();
      await this.trackRealtimeVisitWithLocation();
      await this.runArchiving();
    }

    this.isSetUp = true;
  }

  async runArchiving() {
    // the reporting pages only view this day
    await MatomoCli.call('core:archive', {
      '--force-idsites': '1',
      '--force-periods': 'day',
      '--force-date-range': `${this.getDateOfVisitTrackedInPast()},${this.getDateOfVisitTrackedInPast()}`,
    });
  }

  async trackRealtimeVisitWithLocation() {
    if (await this.isRealtimeVisitAlreadyTracked()) {
      return;
    }

    const baseUrl = await Website.baseUrl();

    // track an action
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cip: '123.45.0.0',
      country: 'kr',
      lat: '35.904232',
      long: '127.391840',
    }));
  }

  async trackVisitsInPast() {
    const baseUrl = await Website.baseUrl();

    // track first visit
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:00:00`,
      urlref: 'http://someotherwebsite.com/page.html',
    }));

    // track download
    await MatomoApi.track('1', new URLSearchParams({
      download: `${baseUrl}/test/page/download.pdf`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:01:00`,
    }));

    // track outlink
    await MatomoApi.track('1', new URLSearchParams({
      link: 'http://anothersite.com/site',
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:02:00`,
    }));

    // track site search
    await MatomoApi.track('1', new URLSearchParams({
      search: 'asearch',
      cdt: `${this.getDateOfVisitTrackedInPast()} 14:03:00`,
    }));

    // another visit that bounces
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 17:00:00`,
      urlref: 'http://facebook.com/some/fbookpage',
    }));

    // third visit with campaign referrer
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page?mtm_campaign=test`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 18:00:00`,
    }));

    // fourth visit with search engine referrer
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 19:00:00`,
      urlref: `http://google.com/search?q=${encodeURIComponent('search term')}`,
    }));

    // fourth visit, direct entry
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Test page',
      url: `${baseUrl}/test/page`,
      cdt: `${this.getDateOfVisitTrackedInPast()} 20:00:00`,
    }));
  }

  async trackOrderInPast() {
    const baseUrl = await Website.baseUrl();

    const _id = 'f494b8a6861e71f0';

    // first a visit with an abandoned cart
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Folding monitors – Matomo for WordPress Test',
      url: `${baseUrl}/?product=folding-monitors`,
      _pkc: '["Uncategorized"]',
      _pkp: '286.00',
      _pks: 'PROD_3',
      _pkn: 'Folding monitors',
      _id,
      cdt: `${this.getDateOfVisitTrackedInPast()} 08:00:00`,
    }));

    // add to cart
    await MatomoApi.track('1', new URLSearchParams({
      idgoal: '0',
      revenue: '286.00',
      ec_items: '[["PROD_3","Folding monitors",["Uncategorized"],286,1]]',
      url: `${baseUrl}/?product=folding-monitors`,
      urlref: `${baseUrl}/?product=folding-monitors`,
      _pkc: '["Uncategorized"]',
      _pkp: '286.00',
      _pks: 'PROD_3',
      _pkn: 'Folding monitors',
      _id,
      cdt: `${this.getDateOfVisitTrackedInPast()} 08:10:00`,
    }));

    // next a visit with an order
    // product page view
    await MatomoApi.track('1', new URLSearchParams({
      action_name: 'Folding monitors – Matomo for WordPress Test',
      url: `${baseUrl}/?product=folding-monitors`,
      _pkc: '["Uncategorized"]',
      _pkp: '286.00',
      _pks: 'PROD_3',
      _pkn: 'Folding monitors',
      _id,
      cdt: `${this.getDateOfVisitTrackedInPast()} 11:00:00`,
    }));

    // add to cart
    await MatomoApi.track('1', new URLSearchParams({
      idgoal: '0',
      revenue: '286.00',
      ec_items: '[["PROD_3","Folding monitors",["Uncategorized"],286,1]]',
      url: `${baseUrl}/?product=folding-monitors`,
      urlref: `${baseUrl}/?product=folding-monitors`,
      _pkc: '["Uncategorized"]',
      _pkp: '286.00',
      _pks: 'PROD_3',
      _pkn: 'Folding monitors',
      _id,
      cdt: `${this.getDateOfVisitTrackedInPast()} 11:05:00`,
    }));

    // order
    await MatomoApi.track('1', new URLSearchParams({
      idgoal: '0',
      revenue: '286.00',
      ec_items: '[["PROD_3","Folding monitors",["Uncategorized"],286,1]]',
      url: `${baseUrl}/?page_id=6`,
      urlref: `${baseUrl}/?product=folding-monitors`,
      _id,
      ec_id: '10000', // sufficiently high enough number to avoid conflicts with other order IDs
      cdt: `${this.getDateOfVisitTrackedInPast()} 11:06:00`,
    }));
  }

  private async isRealtimeVisitAlreadyTracked() {
    const realTimeVisit = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: 'today',
      period: 'month',
      filter_limit: '1',
      format: 'json',
    }));

    return realTimeVisit instanceof Array && realTimeVisit.length > 0;
  }

  private async isVisitAlreadyTracked() {
    const visitInDay = await MatomoApi.call('GET', 'Live.getLastVisitsDetails', new URLSearchParams({
      idSite: '1',
      date: this.getDateOfVisitTrackedInPast(),
      period: 'week',
      filter_limit: '1',
      format: 'json',
    }));

    return visitInDay instanceof Array && visitInDay.length > 0;
  }

  private async createTestGoal() {
    const goal = await MatomoApi.call('GET', 'Goals.getGoal', new URLSearchParams({
      idSite: '1',
      idGoal: '1',
    }));

    if (goal.idgoal) { // already created
      this._testIdGoal = goal.idgoal;
      return;
    }

    const response = await MatomoApi.call('POST', 'Goals.addGoal', new URLSearchParams({
      idSite: '1',
      name: 'test goal',
      matchAttribute: 'url',
      pattern: 'test',
      patternType: 'contains',
      revenue: '2',
    }));

    this._testIdGoal = parseInt(response.value, 10);
  }

  private async addTagManagerEntities() {
    const existingContainersResponse = await MatomoApi.call('GET', 'TagManager.getContainers');
    const existingContainers = this.asList(existingContainersResponse);

    let existingContainer = existingContainers.find((c) => c.name === 'test');
    if (existingContainer) {
      this._testIdContainer = existingContainer.idcontainer;
      return;
    }

    // create container
    const idContainer = await MatomoApi.call('POST', 'TagManager.addContainer', new URLSearchParams({
      idSite: '1',
      context: 'web',
      name: 'test',
      description: 'container for e2e tests',
    }));

    const draftVersionId = await this.getDraftVersionOf('1', idContainer);

    // create trigger
    const triggersResponse = await MatomoApi.call('GET', 'TagManager.getContainerTriggers', new URLSearchParams({
      idSite: '1',
      idContainer,
      idContainerVersion: draftVersionId,
    }));
    const triggers = this.asList(triggersResponse);

    let triggerId;
    if (!triggers.length) {
      triggerId = await MatomoApi.call('POST', 'TagManager.addContainerTrigger', new URLSearchParams({
        idSite: '1',
        idContainer,
        idContainerVersion: draftVersionId,
        type: 'AllElementsClick',
        name: 'All Elements Click',
        description: 'test trigger',
        'conditions[0][comparison]': 'equals',
        'conditions[0][actual]': 'ClickId',
        'conditions[0][expected]': 'tagmanager-test-element',
      }));
    } else {
      triggerId = triggers[0].idtrigger;
    }

    // create variable
    const variablesResponse = await MatomoApi.call('GET', 'TagManager.getContainerVariables', new URLSearchParams({
      idSite: '1',
      idContainer,
      idContainerVersion: draftVersionId,
    }));
    const variables = this.asList(variablesResponse);

    if (!variables.length) {
      await MatomoApi.call('POST', 'TagManager.addContainerVariable', new URLSearchParams({
        idSite: '1',
        idContainer,
        idContainerVersion: draftVersionId,
        type: 'CustomJsFunction',
        name: 'test variable',
        description: 'test variable',
        'parameters[jsFunction]': 'function () { return "test value"; }',
      }));
    }

    // create tag
    const tagsResponse = await MatomoApi.call('GET', 'TagManager.getContainerTags', new URLSearchParams({
      idSite: '1',
      idContainer,
      idContainerVersion: draftVersionId,
    }));
    const tags = this.asList(tagsResponse);

    if (!tags.length) {
      await MatomoApi.call('POST', 'TagManager.addContainerTag', new URLSearchParams({
        idSite: '1',
        idContainer,
        idContainerVersion: draftVersionId,
        type: 'CustomHtml',
        name: 'test Custom HTML',
        description: 'test tag',
        fireLimit: 'unlimited',
        fireDelay: '0',
        priority: '999',
        'parameters[customHtml]': `<div
  id="test-tagmanager-added-div"
  var-value="{{test variable}}"
>
</div>`,
        'parameters[htmlPosition]': 'bodyEnd',
        'fireTriggerIds[]': `${triggerId}`,
      }));
    }

    // create container version
    const publishVersionId = await MatomoApi.call('POST', 'TagManager.createContainerVersion', new URLSearchParams({
      idSite: '1',
      idContainer,
      name: '1.0',
      description: 'test version',
    }));

    await MatomoApi.call('POST', 'TagManager.publishContainerVersion', new URLSearchParams({
      idSite: '1',
      idContainer,
      idContainerVersion: publishVersionId,
      environment: 'live',
    }));

    this._testIdContainer = idContainer;
  }

  private async getDraftVersionOf(idSite: string, idContainer: string): Promise<string> {
    const response = await MatomoApi.call('GET', 'TagManager.getContainer', new URLSearchParams({
      idSite,
      idContainer,
    }));
    const container = response?.value ?? response?.data ?? response;
    const draftVersionId = container?.draft?.idcontainerversion;

    if (!draftVersionId) {
      throw new Error(`TagManager.getContainer did not return a draft version: ${JSON.stringify(response)}`);
    }

    return String(draftVersionId);
  }

  private asList<T>(response: unknown): T[] {
    if (Array.isArray(response)) {
      return response as T[];
    }

    if (!response || typeof response !== 'object') {
      return [];
    }

    const responseObject = response as Record<string, unknown>;
    const wrappedList = responseObject.value ?? responseObject.data ?? responseObject.containers;
    if (Array.isArray(wrappedList)) {
      return wrappedList as T[];
    }
    if (wrappedList && typeof wrappedList === 'object') {
      return Object.values(wrappedList).filter(
        (item): item is T => item !== null && typeof item === 'object'
      );
    }

    return Object.values(responseObject).filter(
      (item): item is T => item !== null && typeof item === 'object'
    );
  }

  getDateOfVisitTrackedInPast() {
    return '2023-12-20';
  }
}

export default new GlobalSetup();
