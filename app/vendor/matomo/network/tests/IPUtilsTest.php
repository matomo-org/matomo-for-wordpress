<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL v3 or later
 */

namespace Tests\Matomo\Network;

use Matomo\Network\IPUtils;
use PHPUnit\Framework\TestCase;

/**
 * @covers Matomo\Network\IPUtils
 */
class IPUtilsTest extends TestCase
{
    public function getIPSanitizationData()
    {
        return array( // input, output
            // single IPv4 address
            array('127.0.0.1', '127.0.0.1'),

            // single IPv6 address (ambiguous)
            array('::1', '::1'),
            array('::ffff:127.0.0.1', '::ffff:127.0.0.1'),
            array('2001:5c0:1000:b::90f8', '2001:5c0:1000:b::90f8'),

            // single IPv6 address
            array('[::1]', '::1'),
            array('[2001:5c0:1000:b::90f8]', '2001:5c0:1000:b::90f8'),
            array('[::ffff:127.0.0.1]', '::ffff:127.0.0.1'),

            // single IPv4 address (CIDR notation)
            array('192.168.1.1/32', '192.168.1.1'),

            // single IPv6 address (CIDR notation)
            array('::1/128', '::1'),
            array('::ffff:127.0.0.1/128', '::ffff:127.0.0.1'),
            array('2001:5c0:1000:b::90f8/128', '2001:5c0:1000:b::90f8'),

            // IPv4 address with port
            array('192.168.1.2:80', '192.168.1.2'),

            // IPv6 address with port
            array('[::1]:80', '::1'),
            array('[::ffff:127.0.0.1]:80', '::ffff:127.0.0.1'),
            array('[2001:5c0:1000:b::90f8]:80', '2001:5c0:1000:b::90f8'),

            // hostnames with port?
            array('localhost', 'localhost'),
            array('localhost:80', 'localhost'),
            array('www.example.com', 'www.example.com'),
            array('example.com:80', 'example.com'),
            array('example.com:8080', 'example.com'),
            array('sub.example.com:8080', 'sub.example.com'),
        );
    }

    /**
     * @dataProvider getIPSanitizationData
     */
    public function testSanitizeIp($ip, $expected)
    {
        $this->assertEquals($expected, IPUtils::sanitizeIp($ip));
    }

    public function getIPRangeSanitizationData()
    {
        return array(
            array('', null),
            array(' 127.0.0.1 ', '127.0.0.1/32'),
            array('192.168.1.0', '192.168.1.0/32'),
            array('192.168.1.1/24', '192.168.1.1/24'),
            array('192.168.1.2/16', '192.168.1.2/16'),
            array('192.168.1.3/8', '192.168.1.3/8'),
            array('192.168.1.4/0', '192.168.1.4/0'),
            array('192.168.1.5/42', null),
            array('192.168.1.6/', null),
            array('192.168.1.7/a', null),
            array('192.168.2.*', '192.168.2.0/24'),
            array('192.169.*.*', '192.169.0.0/16'),
            array('193.*.*.*', '193.0.0.0/8'),
            array('*.*.*.*', '0.0.0.0/0'),
            array('*.*.*.1', null),
            array('*.*.1.1', null),
            array('*.1.1.1', null),
            array('1.*.1.1', null),
            array('1.1.*.1', null),
            array('1.*.*.1', null),
            array('1.1.1.**', null),
            array('1.1.1.1*', null),
            array('1.1.1.*1', null),
            array('1.1.1.1**', null),
            array('1.1.1.1*2', null),
            array('1.1.1.*/24', null),
            array('::1', '::1/128'),
            array('::ffff:127.0.0.1', '::ffff:127.0.0.1/128'),
            array('2001:5c0:1000:b::90f8', '2001:5c0:1000:b::90f8/128'),
            array('::1/64', '::1/64'),
            array('::1/129', null),
            array('::1/', null),
            array('::1/a', null),
            array('::ffff:127.0.0.1/64', '::ffff:127.0.0.1/64'),
            array('2001:5c0:1000:b::90f8/64', '2001:5c0:1000:b::90f8/64'),
            array('1:2:3:4:5:6:7:8', '1:2:3:4:5:6:7:8/128'),
            array('1:2:3:4:5:6:7:*', '1:2:3:4:5:6:7:0/112'),
            array('1:2:3:4:5:6:*:*', '1:2:3:4:5:6:0:0/96'),
            array('1:2:3:4:5:*:*:*', '1:2:3:4:5:0:0:0/80'),
            array('1:2:3:4:*:*:*:*', '1:2:3:4:0:0:0:0/64'),
            array('1:2:3:*:*:*:*:*', '1:2:3:0:0:0:0:0/48'),
            array('1:2:*:*:*:*:*:*', '1:2:0:0:0:0:0:0/32'),
            array('1:*:*:*:*:*:*:*', '1:0:0:0:0:0:0:0/16'),
            array('*:*:*:*:*:*:*:*', '0:0:0:0:0:0:0:0/0'),
            array('*:2:3:4:5:6:7:8', null),
            array('::*', '::0/112'),
            array('::*/112', null),
            array('::7:*', '::7:0/112'),
            array('1::*', '1::0/112'),
            array('::**', null),
            array('*::', null),
            array('*::8', null),
            array('*:2::8', null),
            array(':*:8', null),
            array('::*:8', null),
            array('::*8', null),
        );
    }

    /**
     * @dataProvider getIPRangeSanitizationData
     */
    public function testSanitizeIpRange($ip, $expected)
    {
        $this->assertSame($expected, IPUtils::sanitizeIpRange($ip));
    }

    public function getIPData()
    {
        return array(
            // IPv4
            array('0.0.0.0', "\x00\x00\x00\x00"),
            array('127.0.0.1', "\x7F\x00\x00\x01"),
            array('192.168.1.12', "\xc0\xa8\x01\x0c"),
            array('255.255.255.255', "\xff\xff\xff\xff"),

            // IPv6
            array('::', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array('::1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01"),
            array('::fffe:7f00:1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xfe\x7f\x00\x00\x01"),
            array('::ffff:127.0.0.1', "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x7f\x00\x00\x01"),
            array('2001:5c0:1000:b::90f8', "\x20\x01\x05\xc0\x10\x00\x00\x0b\x00\x00\x00\x00\x00\x00\x90\xf8"),
        );
    }

    /**
     * @dataProvider getIPData
     */
    public function testStringToBinaryIP($string, $binary)
    {
        $this->assertEquals($binary, IPUtils::stringToBinaryIP($string));
    }

    public function getInvalidIPData()
    {
        return array(
            // not a series of dotted numbers
            array(null),
            array(''),
            array('alpha'),
            array('...'),

            // missing an octet
            array('.0.0.0'),
            array('0..0.0'),
            array('0.0..0'),
            array('0.0.0.'),

            // octets must be 0-255
            array('-1.0.0.0'),
            array('1.1.1.256'),

            // leading zeros not supported (i.e., can be ambiguous, e.g., octal)
//            array('07.07.07.07'),
        );
    }

    /**
     * @dataProvider getInvalidIPData
     */
    public function testStringToBinaryInvalidIP($stringIp)
    {
        $this->assertEquals("\x00\x00\x00\x00", IPUtils::stringToBinaryIP($stringIp));
    }

    public function getBinaryIPData()
    {
        // a valid network address is either 4 or 16 bytes; those lines are intentionally left blank ;)
        return array(
            array(null),
            array(''),
            array("\x01"),
            array("\x01\x00"),
            array("\x01\x00\x00"),

            array("\x01\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),

            array("\x01\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
        );
    }

    /**
     * @dataProvider getIPData
     */
    public function testBinaryToStringIP($string, $binary)
    {
        $this->assertEquals($string, IPUtils::binaryToStringIP($binary));
    }

    /**
     * @dataProvider getBinaryIPData
     */
    public function testBinaryToStringInvalidIP($binary)
    {
        $this->assertEquals('0.0.0.0', IPUtils::binaryToStringIP($binary), bin2hex($binary));
    }

    public function getBoundsForIPRangeTest()
    {
        return array(

            // invalid ranges
            array(null, null),
            array('', null),
            array('0', null),
            array('192.168.255.255/33', null),
            array('192.168.255.255/-1', null),
            array('192.168.1.1/', null),

            // single IPv4
            array('127.0.0.1', array("\x7f\x00\x00\x01", "\x7f\x00\x00\x01")),

            // IPv4 with wildcards
            array('192.168.1.*', array("\xc0\xa8\x01\x00", "\xc0\xa8\x01\xff")),
            array('192.168.*.*', array("\xc0\xa8\x00\x00", "\xc0\xa8\xff\xff")),
            array('192.*.*.*', array("\xc0\x00\x00\x00", "\xc0\xff\xff\xff")),
            array('*.*.*.*', array("\x00\x00\x00\x00", "\xff\xff\xff\xff")),

            // single IPv4 in expected CIDR notation
            array('192.168.1.1/24', array("\xc0\xa8\x01\x00", "\xc0\xa8\x01\xff")),

            array('192.168.1.127/32', array("\xc0\xa8\x01\x7f", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/31', array("\xc0\xa8\x01\x7e", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/30', array("\xc0\xa8\x01\x7c", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/29', array("\xc0\xa8\x01\x78", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/28', array("\xc0\xa8\x01\x70", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/27', array("\xc0\xa8\x01\x60", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/26', array("\xc0\xa8\x01\x40", "\xc0\xa8\x01\x7f")),
            array('192.168.1.127/25', array("\xc0\xa8\x01\x00", "\xc0\xa8\x01\x7f")),

            array('192.168.1.255/32', array("\xc0\xa8\x01\xff", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/31', array("\xc0\xa8\x01\xfe", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/30', array("\xc0\xa8\x01\xfc", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/29', array("\xc0\xa8\x01\xf8", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/28', array("\xc0\xa8\x01\xf0", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/27', array("\xc0\xa8\x01\xe0", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/26', array("\xc0\xa8\x01\xc0", "\xc0\xa8\x01\xff")),
            array('192.168.1.255/25', array("\xc0\xa8\x01\x80", "\xc0\xa8\x01\xff")),

            array('192.168.255.255/24', array("\xc0\xa8\xff\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/23', array("\xc0\xa8\xfe\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/22', array("\xc0\xa8\xfc\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/21', array("\xc0\xa8\xf8\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/20', array("\xc0\xa8\xf0\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/19', array("\xc0\xa8\xe0\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/18', array("\xc0\xa8\xc0\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/17', array("\xc0\xa8\x80\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/16', array("\xc0\xa8\x00\x00", "\xc0\xa8\xff\xff")),
            array('192.168.255.255/15', array("\xc0\xa8\x00\x00", "\xc0\xa9\xff\xff")),
            array('192.168.255.255/14', array("\xc0\xa8\x00\x00", "\xc0\xab\xff\xff")),
            array('192.168.255.255/13', array("\xc0\xa8\x00\x00", "\xc0\xaf\xff\xff")),
            array('192.168.255.255/12', array("\xc0\xa0\x00\x00", "\xc0\xaf\xff\xff")),
            array('192.168.255.255/11', array("\xc0\xa0\x00\x00", "\xc0\xbf\xff\xff")),
            array('192.168.255.255/10', array("\xc0\x80\x00\x00", "\xc0\xbf\xff\xff")),
            array('192.168.255.255/9', array("\xc0\x80\x00\x00", "\xc0\xff\xff\xff")),
            array('192.168.255.255/8', array("\xc0\x00\x00\x00", "\xc0\xff\xff\xff")),
            array('192.168.255.255/7', array("\xc0\x00\x00\x00", "\xc1\xff\xff\xff")),
            array('192.168.255.255/6', array("\xc0\x00\x00\x00", "\xc3\xff\xff\xff")),
            array('192.168.255.255/5', array("\xc0\x00\x00\x00", "\xc7\xff\xff\xff")),
            array('192.168.255.255/4', array("\xc0\x00\x00\x00", "\xcf\xff\xff\xff")),
            array('192.168.255.255/3', array("\xc0\x00\x00\x00", "\xdf\xff\xff\xff")),
            array('192.168.255.255/2', array("\xc0\x00\x00\x00", "\xff\xff\xff\xff")),
            array('192.168.255.255/1', array("\x80\x00\x00\x00", "\xff\xff\xff\xff")),
            array('0.0.0.0/0', array("\x00\x00\x00\x00", "\xff\xff\xff\xff")),

            // single IPv6
            array('::1', array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01")),

            // single IPv6 in expected CIDR notation
            array('::1/128', array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01", "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01")),
            array('::1/127', array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x01")),
            array('::fffe:7f00:1/120', array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xfe\x7f\x00\x00\x00", "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xfe\x7f\x00\x00\xff")),
            array('::ffff:127.0.0.1/120', array("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x7f\x00\x00\x00", "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x7f\x00\x00\xff")),

            array('2001:ca11:911::b0b:15:dead/128', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xad", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xad")),
            array('2001:ca11:911::b0b:15:dead/127', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xac", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xad")),
            array('2001:ca11:911::b0b:15:dead/126', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xac", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xaf")),
            array('2001:ca11:911::b0b:15:dead/125', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xa8", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xaf")),
            array('2001:ca11:911::b0b:15:dead/124', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xa0", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xaf")),
            array('2001:ca11:911::b0b:15:dead/123', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xa0", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xbf")),
            array('2001:ca11:911::b0b:15:dead/122', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\x80", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xbf")),
            array('2001:ca11:911::b0b:15:dead/121', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\x80", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xff")),
            array('2001:ca11:911::b0b:15:dead/120', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\xff")),
            array('2001:ca11:911::b0b:15:dead/119', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xde\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdf\xff")),
            array('2001:ca11:911::b0b:15:dead/118', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdc\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdf\xff")),
            array('2001:ca11:911::b0b:15:dead/117', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xd8\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdf\xff")),
            array('2001:ca11:911::b0b:15:dead/116', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xd0\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdf\xff")),
            array('2001:ca11:911::b0b:15:dead/115', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xc0\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xdf\xff")),
            array('2001:ca11:911::b0b:15:dead/114', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xc0\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xff\xff")),
            array('2001:ca11:911::b0b:15:dead/113', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\x80\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xff\xff")),
            array('2001:ca11:911::b0b:15:dead/112', array("\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\x00\x00", "\x20\x01\xca\x11\x09\x11\x00\x00\x00\x00\x0b\x0b\x00\x15\xff\xff")),
        );
    }

    /**
     * @dataProvider getBoundsForIPRangeTest
     */
    public function testGetIPRangeBounds($range, $expected)
    {
        $this->assertSame($expected, IPUtils::getIPRangeBounds($range));
    }
}
