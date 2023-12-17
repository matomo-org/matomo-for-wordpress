<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\NullCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\NullCache
 */
class NullCacheTest extends TestCase
{
    /**
     * @var NullCache
     */
    private $cache;

    private $cacheId = 'testid';

    protected function setUp()
    {
        $this->cache = new NullCache();
        $this->cache->doSave($this->cacheId, 'anyvalue', 100);
    }

    public function test_doSave_shouldAlwaysReturnTrue()
    {
        $this->assertTrue($this->cache->doSave('randomid', 'anyvalue', 100));
    }

    public function test_doFetch_shouldAlwaysReturnFalse_EvenIfSomethingWasSet()
    {
        $this->assertFalse($this->cache->doFetch($this->cacheId));
    }

    public function test_doContains_shouldAlwaysReturnFalse_EvenIfSomethingWasSet()
    {
        $this->assertFalse($this->cache->doContains($this->cacheId));
    }

    public function test_doDelete_shouldAlwaysPretendItWorked_EvenIfNoSuchKeyExists()
    {
        $this->assertTrue($this->cache->doDelete('loremipsum'));
    }

    public function test_doFlush_shouldAlwaysPretendItWorked()
    {
        $this->assertTrue($this->cache->doFlush());
    }

}
