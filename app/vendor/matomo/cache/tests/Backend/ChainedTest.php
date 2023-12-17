<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\ArrayCache;
use Matomo\Cache\Backend\Chained;
use Matomo\Cache\Backend\NullCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\Chained
 */
class ChainedTest extends TestCase
{

    public function test_constructor_getbackends_shouldMakeSureToHaveProperIndex()
    {
        $arrayCache = new ArrayCache();
        $nullCache  = new NullCache();

        $backends = array(0 => $arrayCache, 2 => $nullCache);
        $cache = $this->createChainedCache($backends);

        $result = $cache->getBackends();
        $this->assertEquals(array($arrayCache, $nullCache), $result);
    }

    public function test_doFetch_shouldPopulateOtherCaches()
    {
        $cacheId = 'myid';
        $cacheValue = 'mytest';

        $arrayCache1 = new ArrayCache();
        $arrayCache2 = new ArrayCache();
        $arrayCache2->doSave($cacheId, $cacheValue);
        $arrayCache3 = new ArrayCache();

        $cache = $this->createChainedCache(array($arrayCache1, $arrayCache2, $arrayCache3));
        $this->assertEquals($cacheValue, $cache->doFetch($cacheId)); // should find the value from second cache

        // should populate previous cache
        $this->assertEquals($cacheValue, $arrayCache1->doFetch($cacheId));

        // should not populate slower cache
        $this->assertFalse($arrayCache3->doContains('myid'));
    }

    private function createChainedCache($backends)
    {
        return new Chained($backends);
    }

}
