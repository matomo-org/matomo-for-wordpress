// wdio config for e2e tests that track data. these are run
// before all others to avoid race conditions.

import { config as baseConfig } from './wdio.conf.js';

export const config = {
  ...baseConfig,
  maxInstances: 1,
  specs: baseConfig.exclude,
  exclude: [],
  onPrepare: null,
};
