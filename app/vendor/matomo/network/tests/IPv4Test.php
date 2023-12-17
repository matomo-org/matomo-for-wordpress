<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Network;

use Matomo\Network\IP;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Network\IPv4
 */
class IPv4Test extends TestCase
{
    public function getIPv4Data()
    {
        return array(
            array(null, '0.0.0.0'),
            array('', '0.0.0.0'),
            array('192.168.0.1', '192.168.0.1'),
        );
    }

    /**
     * @dataProvider getIPv4Data
     */
    public function testToIPv4String($stringIp, $expected)
    {
        $ip = IP::fromStringIP($stringIp);

        $this->assertInstanceOf('Matomo\Network\IPv4', $ip);

        $this->assertEquals($expected, $ip->toIPv4String(), $stringIp);
    }

    public function getAddressesToAnonymize()
    {
        return array(
            // ip, array( expected0, expected1, expected2, expected3, expected4 ),
            array('0.0.0.0', array("\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.0.0.1', array("\x00\x00\x00\x01", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.0.0.255', array("\x00\x00\x00\xff", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.0.1.0', array("\x00\x00\x01\x00", "\x00\x00\x01\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.0.1.1', array("\x00\x00\x01\x01", "\x00\x00\x01\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.0.255.255', array("\x00\x00\xff\xff", "\x00\x00\xff\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.1.0.0', array("\x00\x01\x00\x00", "\x00\x01\x00\x00", "\x00\x01\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.1.1.1', array("\x00\x01\x01\x01", "\x00\x01\x01\x00", "\x00\x01\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('0.255.255.255', array("\x00\xff\xff\xff", "\x00\xff\xff\x00", "\x00\xff\x00\x00", "\x00\x00\x00\x00", "\x00\x00\x00\x00")),
            array('1.0.0.0', array("\x01\x00\x00\x00", "\x01\x00\x00\x00", "\x01\x00\x00\x00", "\x01\x00\x00\x00", "\x00\x00\x00\x00")),
            array('127.255.255.255', array("\x7f\xff\xff\xff", "\x7f\xff\xff\x00", "\x7f\xff\x00\x00", "\x7f\x00\x00\x00", "\x00\x00\x00\x00")),
            array('128.0.0.0', array("\x80\x00\x00\x00", "\x80\x00\x00\x00", "\x80\x00\x00\x00", "\x80\x00\x00\x00", "\x00\x00\x00\x00")),
            array('255.255.255.255', array("\xff\xff\xff\xff", "\xff\xff\xff\x00", "\xff\xff\x00\x00", "\xff\x00\x00\x00", "\x00\x00\x00\x00")),
        );
    }

    /**
     * @dataProvider getAddressesToAnonymize
     */
    public function testAnonymize($ipString, $expected)
    {
        $ip = IP::fromStringIP($ipString);

        $this->assertInstanceOf('Matomo\Network\IPv4', $ip);

        // each IP is tested with 0 to 4 octets masked
        for ($byteCount = 0; $byteCount <= 4; $byteCount++) {
            $result = $ip->anonymize($byteCount);
            $this->assertEquals($expected[$byteCount], $result->toBinary(), "Got $result, Expected " . bin2hex($expected[$byteCount]));
        }

        // edge case (bounds check)
        $this->assertEquals("\x00\x00\x00\x00", $ip->anonymize(5)->toBinary());
    }
}
