<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Network;

use Matomo\Network\IP;
use Matomo\Network\IPv6;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Matomo\Network\IPv6
 */
class IPv6Test extends TestCase
{
    public function getIPv6Data()
    {
        return array(
            array('::ffff:192.168.0.1', '192.168.0.1'),
            array('2001:5c0:1000:b::90f8', null),
        );
    }

    /**
     * @dataProvider getIPv6Data
     */
    public function testToIPv4String($stringIp, $expected)
    {
        $ip = IP::fromStringIP($stringIp);

        $this->assertInstanceOf('Matomo\Network\IPv6', $ip);

        $this->assertEquals($expected, $ip->toIPv4String(), $stringIp);
    }

    public function getMappedIPv4Data()
    {
        return array(
            array(IP::fromStringIP('::ffff:192.168.0.1'), true),
            array(IP::fromStringIP('2001:5c0:1000:b::90f8'), false),

            // IPv4-mapped (RFC 4291, 2.5.5.2)
            array(IP::fromBinaryIP("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\xc0\xa8\x01\x02"), true),
            // IPv4-compatible (this transitional format is deprecated in RFC 4291, section 2.5.5.1)
            array(IP::fromBinaryIP("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xc0\xa8\x01\x01"), true),

            // other IPv6 address
            array(IP::fromBinaryIP("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\x00\xc0\xa8\x01\x03"), false),
            array(IP::fromBinaryIP("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01\xc0\xa8\x01\x04"), false),
            array(IP::fromBinaryIP("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xc0\xa8\x01\x05"), false),
        );
    }

    /**
     * @dataProvider getMappedIPv4Data
     */
    public function testIsMappedIPv4(IPv6 $ip, $isMapped)
    {
        $this->assertEquals($isMapped, $ip->isMappedIPv4(), $ip);
    }

    public function getAddressesToAnonymize()
    {
        return array(
            array('2001:db8:0:8d3:0:8a2e:70:7344', array(
                "\x20\x01\x0d\xb8\x00\x00\x08\xd3\x00\x00\x8a\x2e\x00\x70\x73\x44",
                "\x20\x01\x0d\xb8\x00\x00\x08\xd3\x00\x00\x00\x00\x00\x00\x00\x00", // mask 64 bits
                "\x20\x01\x0d\xb8\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", // mask 80 bits
                "\x20\x01\x0d\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", // mask 104 bits
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00" // mask all bits
            )),
            array('2001:6f8:900:724::2', array(
                "\x20\x01\x06\xf8\x09\x00\x07\x24\x00\x00\x00\x00\x00\x00\x00\x02",
                "\x20\x01\x06\xf8\x09\x00\x07\x24\x00\x00\x00\x00\x00\x00\x00\x00",
                "\x20\x01\x06\xf8\x09\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                "\x20\x01\x06\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            ))
        );
    }

    /**
     * @dataProvider getAddressesToAnonymize
     */
    public function testAnonymize($ipString, $expected)
    {
        $ip = IP::fromStringIP($ipString);

        $this->assertInstanceOf('Matomo\Network\IPv6', $ip);

        // each IP is tested with 0 to 4 octets masked
        for ($byteCount = 0; $byteCount <= 4; $byteCount++) {
            $result = $ip->anonymize($byteCount);
            $this->assertEquals($expected[$byteCount], $result->toBinary(), "Got $result, Expected " . bin2hex($expected[$byteCount]) . ", Mask: " . $byteCount);
        }
    }


    public function getIPv4AddressesToAnonymize()
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
     * @dataProvider getIPv4AddressesToAnonymize
     */
    public function testAnonymizeIPv4MappedAdresses($ipString, $expected)
    {
        $ip = IP::fromStringIP('::ffff:' . $ipString);

        $this->assertInstanceOf('Matomo\Network\IPv6', $ip);

        // mask IPv4 mapped addresses
        for ($byteCount = 0; $byteCount <= 4; $byteCount++) {
            $result = $ip->anonymize($byteCount);
            $expectedIp = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . $expected[$byteCount];
            $this->assertEquals($expectedIp, $result->toBinary(), "Got $result, Expected " . bin2hex($expectedIp));
        }

        $this->assertEquals("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\x00\x00\x00\x00\x00", $ip->anonymize(5)->toBinary());
    }
}
