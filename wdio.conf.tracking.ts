// wdio config for e2e tests that must run in sequence, ie, ones that track data. these
// use their own WordPress install, so they run concurrently with the main run.

import { config as baseConfig } from './wdio.conf.js';

// inherited by the worker processes
process.env.E2E_SERIAL_RUN = '1';

export const config = {
  ...baseConfig,
  maxInstances: 1,
  specs: baseConfig.exclude,
  exclude: [],
  onPrepare: null,
};
