#!/usr/bin/env node
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * https://github.com/braekling/matomo
 *
 */

/**
 * This script is a manual testing aid. It three basic tests against
 * specific MWP install. These tests include:
 *
 * - test that no bot is recorded for a normal pageview with no AI bot user agent
 * - test that a bot is recorded for a normal pageview when an AI bot user agent
 *   is in use
 * - test that when a browser that executes JavaScript views a page with an AI bot
 *   user agent, at least one AI bot is logged.
 *   - test that after the first pageview, subsequent pageviews with a browser that
 *     executes JavaScript, only one AI bot request is recorded per pageview.
 *
 * The idea is to change the environment, by installing different plugins, enabling
 * different settings, using different web servers, then run this script to quickly
 * see if AI bot tracking works for that setup.
 *
 * Before running this script, make sure to customize the consts to point to your
 * WordPress install.
 */

import mysql from 'mysql2/promise';
import fetch from 'node-fetch';
import chalk from 'chalk';
import puppeteer from 'puppeteer';

const MYSQL_HOST = 'localhost';
const MYSQL_PORT = 3307;
const MYSQL_USER = 'root';
const MYSQL_PASS = 'pass';
const MYSQL_DATABASE = 'wp_matomo_6_9';

const MATOMO_BASE_URL = 'http://localhost/6.9';

const USER_AGENT_CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36';
const USER_AGENT_AI_BOT = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)';

const connection = await mysql.createConnection({
  host: MYSQL_HOST,
  user: MYSQL_USER,
  password: MYSQL_PASS,
  database: MYSQL_DATABASE,
  port: MYSQL_PORT,
});

async function getLogBotRequestCount() {
  const [ results ] = await connection.query('SELECT COUNT(*) AS count FROM wp_matomo_log_bot_request');
  return results[0].count;
}

async function pageview(userAgent) {
  const url = `${MATOMO_BASE_URL}/`;

  const response = await fetch(url, {
    headers: {
      'User-Agent': userAgent,
    },
  });

  if (response.status < 200 || response.status > 299) {
    console.log(chalk.red(`  Pageview request failed! Response code: ${response.status}`));
    console.log(await response.text());
  }

  await new Promise((resolve) => setTimeout(resolve, 2000));
}

/**
 * Check that viewing a page without an AI user agent does not log
 * a bot request.
 */
async function trackNormalPageView() {
  const botRequestCountBefore = await getLogBotRequestCount();

  await pageview(USER_AGENT_CHROME);

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackNormalPageView:');
  console.log(`  does not create bot request: ${chalk.green(botRequestCountBefore === botRequestCountAfter ? 'Y' : 'N')}`);
}

/**
 * Check that viewing a page with an AI user agent logs a single
 * bot request. (Does not execute JavaScript.)
 */
async function trackPageViewWithAiUserAgent() {
  const botRequestCountBefore = await getLogBotRequestCount();

  await pageview(USER_AGENT_AI_BOT);

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackPageViewWithAiUserAgent:');
  console.log(`  creates bot request: ${chalk.green(botRequestCountBefore < botRequestCountAfter ? 'Y' : 'N')}`);
}

/**
 * Check that viewing a page with a browser that executes JavaScript
 * will create at least one logged bot request.
 *
 * Then checks that subsequent page views in this browser do not log
 * more than one bot request per pageview.
 */
async function trackTwoPageViewsWithPuppeteerAndAiUserAgent(browser) {
  const botRequestCountBefore = await getLogBotRequestCount();

  const page = await browser.newPage();
  await page.setUserAgent(USER_AGENT_AI_BOT);

  try {
    await page.goto(`${MATOMO_BASE_URL}/`);
    await page.waitForNetworkIdle();
    await new Promise(resolve => setTimeout(resolve, 3000));
  } catch (e) {
    await page.screenshot({ fullPage: true, path: './failure.png' });
    throw e;
  }

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackTwoPageViewsWithPuppeteerAndAiUserAgent:');
  console.log(`  creates bot request: ${chalk.green(botRequestCountBefore < botRequestCountAfter ? 'Y' : 'N')}`);

  // reload page after matomo_has_js cookie is set
  try {
    await page.reload();
    await page.waitForNetworkIdle();
    await new Promise(resolve => setTimeout(resolve, 3000));
  } catch (e) {
    await page.screenshot({ fullPage: true, path: './failure.png' });
    throw e;
  }

  const botRequestCountAfterReload = await getLogBotRequestCount();

  console.log(`  creates single bot request: ${chalk.green(botRequestCountAfterReload - botRequestCountAfter === 1 ? 'Y' : 'N')}`);
}

async function runTests() {
  const browser = await puppeteer.launch({
    headless: true,
  });

  try {
    await trackNormalPageView();
    await trackPageViewWithAiUserAgent();
    await trackTwoPageViewsWithPuppeteerAndAiUserAgent(browser);
  } finally {
    await browser.close();
  }
}

runTests()
  .then(() => {
    console.log('Done');
  })
  .catch((e) => {
    console.log(`Error: ${e.stack || e.message || e}`);
  })
  .finally(() => {
    connection.destroy();
  });
