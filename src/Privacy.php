<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use ParagonIE\ConstantTime\Binary;
use Soatok\DholeCrypto\Exceptions\CryptoException;
use Soatok\DholeCrypto\Key\SymmetricKey;
use Soatok\DholeCrypto\Keyring;

/**
 * Class Privacy
 * @package Soatok\AnthroKit
 */
class Privacy
{
    /**
     * @param string $dir
     * @return SymmetricKey
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function getDailyIPMaskKey(string $dir = ''): SymmetricKey
    {
        if (!$dir) {
            $dir = sys_get_temp_dir();
        }
        $keyring = new Keyring();
        $today = $dir . DIRECTORY_SEPARATOR . date('Ymd') . '.key';
        if (file_exists($today)) {
            $key = $keyring->load(file_get_contents($today));
            if ($key instanceof SymmetricKey) {
                return $key;
            }
        }
        $key = SymmetricKey::generate();
        file_put_contents($today, $keyring->save($key));
        return $key;
    }

    /**
     * Anonymize an IP address with SipHash-2-4. Uses, by default,
     * a per-day masking key.
     *
     * @param string $ip
     * @param SymmetricKey|null $key
     * @return string
     *
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function maskIPv4(string $ip, SymmetricKey $key = null): string
    {
        if (!$key) {
            $key = $this->getDailyIPMaskKey();
        }
        $siphashKey = sodium_crypto_generichash(
            $key->getRawKeyMaterial(),
            '',
            SODIUM_CRYPTO_SHORTHASH_KEYBYTES
        );
        $packed = 'AnthroKit_IPv4_Mask' . pack('P', ip2long($ip));
        $hashed = sodium_crypto_shorthash($packed, $siphashKey);
        $unpacked = unpack('V', Binary::safeSubstr($hashed, 0, 4));
        return long2ip($unpacked[1]);
    }
}
