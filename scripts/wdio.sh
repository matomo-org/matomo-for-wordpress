#!/bin/bash

EXIT_STATUS=0
wdio run ./wdio.conf.tracking.ts && wdio run ./wdio.conf.ts || EXIT_STATUS=$?
wdio run ./wdio.conf.uninstall.ts || EXIT_STATUS=$?
exit $EXIT_STATUS
