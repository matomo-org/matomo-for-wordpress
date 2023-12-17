<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Cache\Backend;

use Matomo\Cache\Backend\DefaultTimeoutDecorated;
use Matomo\Cache\Backend\NullCache;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Cache\Backend\DefaultTimeoutDecorated
 */
class DefaultTimeoutTest extends TestCase
{
    /**
     * @var DefaultTimeoutDecorated
     */
    private $cache;

    /**
     * @var NullCache
     */
    private $backendMock;


    private $defaultTTl = 555;

    public function setUp()
    {
        $this->backendMock = $this->getMockBuilder(NullCache::class)->getMock();

        $opts = ['defaultTimeout'=>$this->defaultTTl];

        $this->cache = new DefaultTimeoutDecorated($this->backendMock, $opts);
    }

    public function test_doSave_shouldCallDecoratedWithDefaultTTL()
    {
        $this->backendMock
            ->expects($this->once())
            ->method('doSave')
            ->with( $this->anything(),
                $this->anything(),
                $this->defaultTTl);

        $this->cache->doSave('randomid', 'anyvalue');
    }
}