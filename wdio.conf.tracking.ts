// wdio config for e2e tests that must run in sequence, ie, ones that track data. these
// use their own WordPress install, so they run concurrently with the main run.

import { config as baseConfig } from './wdio.conf.js';

export const config = {
  ...baseConfig,
  maxInstances: 1,
  specs: baseConfig.exclude,
  exclude: [],
  onPrepare: null,
};
