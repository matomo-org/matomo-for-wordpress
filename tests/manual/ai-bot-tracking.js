#!/usr/bin/env node

import mysql from 'mysql2/promise';
import fetch from 'node-fetch';
import chalk from 'chalk';
import puppeteer from 'puppeteer';

const MYSQL_HOST = 'localhost';
const MYSQL_PORT = 3307;
const MYSQL_USER = 'root';
const MYSQL_PASS = 'pass';
const MYSQL_DATABASE = 'wp_matomo_6_9'; // TODO: use env var in .env?

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

/**
 * TODO describe script and how to use
 */

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
  }

  await new Promise((resolve) => setTimeout(resolve, 2000));
}

/**
 * TODO describe test
 */
async function trackNormalPageView() {
  const botRequestCountBefore = await getLogBotRequestCount();

  await pageview(USER_AGENT_CHROME);

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackNormalPageView:');
  console.log(`  does not create bot request: ${chalk.green(botRequestCountBefore === botRequestCountAfter ? 'Y' : 'N')}`);
}

/**
 * TODO describe test
 */
async function trackPageViewWithAiUserAgent() {
  const botRequestCountBefore = await getLogBotRequestCount();

  await pageview(USER_AGENT_AI_BOT);

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackPageViewWithAiUserAgent:');
  console.log(`  creates bot request: ${chalk.green(botRequestCountBefore < botRequestCountAfter ? 'Y' : 'N')}`);
}

/**
 * TODO describe test
 */
async function trackTwoPageViewsWithPuppeteerAndAiUserAgent() {
  const botRequestCountBefore = await getLogBotRequestCount();

  const browser = await puppeteer.launch({
    headless: 'new',
  });

  const page = await browser.newPage();
  await page.setUserAgent(USER_AGENT_AI_BOT);

  try {
    await page.goto(`${MATOMO_BASE_URL}/`);
    await page.waitForNetworkIdle();
    await new Promise(resolve => setTimeout(resolve, 3000));
  } catch (e) {
    await page.screenshot({ fullPage: true, path: './failure.png' });
    throw e;
  } finally {
    await browser.close();
  }

  const botRequestCountAfter = await getLogBotRequestCount();

  console.log('trackTwoPageViewsWithPuppeteerAndAiUserAgent:');
  console.log(`  creates bot request: ${chalk.green(botRequestCountBefore < botRequestCountAfter ? 'Y' : 'N')}`);
  console.log(`  creates single bot request: ${chalk.green(botRequestCountAfter - botRequestCountBefore === 1 ? 'Y' : 'N')}`);
}

async function runTests() {
  await trackNormalPageView();
  await trackPageViewWithAiUserAgent();
  await trackTwoPageViewsWithPuppeteerAndAiUserAgent();
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
