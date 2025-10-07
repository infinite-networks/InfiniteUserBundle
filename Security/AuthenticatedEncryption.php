<?php declare(strict_types=1);
/*
 * (c) Infinite Networks Pty Ltd <https://www.infinite.net.au>
 */

namespace Infinite\UserBundle\Security;

class AuthenticatedEncryption
{
    private string $key;

    public function __construct(string $secret)
    {
        // Generate the encryption key.
        // Use a low iteration count for performance and because kernel.secret is already locked down.
        $this->key = openssl_pbkdf2($secret, 'E3pFLbSC', 16, 1000);
    }

    /**
     * Encrypts $data and returns a hexadecimal string.
     *
     * Uses kernel.secret as the encryption key.
     */
    public function encrypt(string $plain): string
    {
        $iv = openssl_random_pseudo_bytes(12);
        $enc = openssl_encrypt($plain, 'aes-128-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return bin2hex($iv . $tag . $enc);
    }

    /**
     * Decrypts $hexData and returns the original $data.
     *
     * Uses kernel.secret as the encryption key.
     *
     * Returns null if there is any error.
     */
    public function decrypt($hexData): ?string
    {
        if (strlen($hexData) % 2 == 1 || strlen($hexData) < 56) {
            return null;
        }

        $data = hex2bin($hexData);
        $iv  = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $enc = substr($data, 28);

        $plain = openssl_decrypt($enc, 'aes-128-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain === false ? null : $plain;
    }
}
