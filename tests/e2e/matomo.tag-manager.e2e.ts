/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

import { expect, browser } from '@wdio/globals';
import Website from './website.js';

describe('Matomo Admin > Diagnostic', () => {
  before(async () => {
    await Website.login();
  });

  it('should run', async () => {
    expect(true).toBeTruthy();
  });
});
