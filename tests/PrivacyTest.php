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
    public function testMask()
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
}
