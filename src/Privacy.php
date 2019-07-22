<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use ParagonIE\ConstantTime\{
    Base32,
    Binary
};
use Slim\Http\{
    Headers,
    Request
};
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
     * @param Request $request
     * @return Request
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function anonymize(Request $request): Request
    {
        $server = $request->getServerParams();
        $server['HTTP_USER_AGENT'] = $this->maskUserAgent($server['HTTP_USER_AGENT']);
        if (preg_match('#^\d\.\d\.\d\.\d$#', $server['REMOTE_ADDR'])) {
            $server['REMOTE_ADDR'] = $this->maskIPv4($server['REMOTE_ADDR']);
        } else {
            $server['REMOTE_ADDR'] = $this->maskIPv6($server['REMOTE_ADDR']);
        }
        return new Request(
            $request->getMethod(),
            $request->getUri(),
            new Headers($request->getHeaders()),
            $request->getCookieParams(),
            $server,
            $request->getBody(),
            $request->getUploadedFiles()
        );
    }

    /**
     * @param int $input
     * @param SymmetricKey|null $key
     * @return int
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function maskInteger(int $input, SymmetricKey $key = null): int
    {
        if (!$key) {
            $key = $this->getDailyIPMaskKey();
        }
        $hashed = sodium_crypto_generichash(
            'AnthroKit_Mask_Integer:' . pack('V', $input),
            $key->getRawKeyMaterial(),
            16
        );
        $unpacked = \unpack('V', Binary::safeSubstr($hashed, 0, 8));
        return $unpacked[1];
    }

    /**
     * Anonymize an IP address with BLAKE2b. Uses, by default,
     * a per-day masking key.
     *
     * @param string $ip
     * @param SymmetricKey|null $key
     * @return string
     *
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function maskIPv6(string $ip, SymmetricKey $key = null): string
    {
        if (!$key) {
            $key = $this->getDailyIPMaskKey();
        }
        $blake2bKey = sodium_crypto_generichash(
            $key->getRawKeyMaterial(),
            '',
            SODIUM_CRYPTO_GENERICHASH_KEYBYTES
        );
        $packed = 'AnthroKit_IPv6_Mask' . inet_pton($ip);
        $hashed = sodium_crypto_generichash($packed, $blake2bKey, 16);
        return inet_ntop($hashed);
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

    /**
     * @param string $userAgent
     * @param SymmetricKey|null $key
     * @return string
     * @throws CryptoException
     * @throws \SodiumException
     */
    public function maskUserAgent(string $userAgent, SymmetricKey $key = null): string
    {
        if (!$key) {
            $key = $this->getDailyIPMaskKey();
        }
        $hashed = sodium_crypto_generichash(
            'AnthroKit_UA_Mask' . $userAgent,
            $key->getRawKeyMaterial(),
            32
        );
        return Base32::encodeUnpadded($hashed);
    }
}
