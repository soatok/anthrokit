<?php
namespace Soatok\AnthroKit\Tests;

use PHPUnit\Framework\TestCase;
use Soatok\AnthroKit\Privacy;
use Soatok\DholeCrypto\Exceptions\CryptoException;
use Soatok\DholeCrypto\Key\SymmetricKey;
use Soatok\DholeCrypto\Keyring;

/**
 * Class PrivacyTest
 * @package Soatok\AnthroKit\Tests
 */
class PrivacyTest extends TestCase
{
    /** @var Privacy $privacy */
    private $privacy;

    public function setUp(): void
    {
        parent::setUp();
        $this->privacy = new Privacy();
    }

    /**
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function testMaskIPv4()
    {
        /** @var SymmetricKey $key */
        $key = (new Keyring())->load(
            'symmetricsIuY4SpxxNe267GEjiLfW4DMeWCS_taPe73clDUPH21_tHKFYX2uAA7QFifXOLvF'
        );
        $random = SymmetricKey::generate();
        $testVectors = [
            ['1.0.0.1', '17.221.113.50'],
            ['255.255.255.255', '189.228.208.89'],
            ['1.2.3.4', '236.88.97.177'],
            ['1.2.3.5', '239.131.242.214'],
            ['8.8.4.4', '242.215.229.186'],
        ];
        foreach ($testVectors as $row) {
            [$in, $out] = $row;
            $this->assertSame(
                $out,
                $this->privacy->maskIPv4($in, $key),
                'Deterministic'
            );
            $this->assertNotSame(
                $out,
                $this->privacy->maskIPv4($in, $random),
                'Random key matched'
            );
        }
    }

    public function testMaskIPv6()
    {
        /** @var SymmetricKey $key */
        $key = (new Keyring())->load(
            'symmetricsIuY4SpxxNe267GEjiLfW4DMeWCS_taPe73clDUPH21_tHKFYX2uAA7QFifXOLvF'
        );
        $random = SymmetricKey::generate();
        $testVectors = [
            ['2001:0db8:85a3:0000:0000:8a2e:0370:733', '132e:d5c5:5754:47e9:7d41:be12:47ee:9eae'],
            ['FE80:0000:0000:0000:0202:B3FF:FE1E:8329', '8db7:c602:223e:2295:be2c:cdf:bd84:94af'],
            ['2001:4860:4860::8888', 'c18c:5604:4a10:e188:59f6:f84e:4730:9945'],
            ['2001:4860:4860::8844', 'a662:9d68:5858:73a9:1906:2e86:1f6:51f4'],
        ];
        foreach ($testVectors as $row) {
            [$in, $out] = $row;
            $this->assertSame(
                $out,
                $this->privacy->maskIPv6($in, $key),
                'Deterministic'
            );
            $this->assertNotSame(
                $out,
                $this->privacy->maskIPv6($in, $random),
                'Random key matched'
            );
        }
    }
}
