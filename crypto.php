<?php

use MyPhpTui\Singleton;

final class StoredPassword
{
    public function __construct(
        public string $name,
        public string $encryptedValue
    ) {}
}

final class EncryptionKey
{
    use Singleton;

    public function __construct(
        private string $value
    ) {}

    public function encrypt(string $text): string
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $key = sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $this->value,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($text, $nonce, $key);
        sodium_memzero($key);
        return base64_encode($salt . $nonce . $ciphertext);
    }

    public function decrypt(string $encodedText): ?string
    {
        $raw = base64_decode($encodedText, true);
        if ($raw === false) {
            return null;
        }
        $saltLength = SODIUM_CRYPTO_PWHASH_SALTBYTES;
        $nonceLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($raw) < $saltLength + $nonceLength) {
            return null;
        }
        $salt = substr($raw, 0, $saltLength);
        $nonce = substr($raw, $saltLength, $nonceLength);
        $ciphertext = substr($raw, $saltLength + $nonceLength);
        $key = sodium_crypto_pwhash(
            SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            $this->value,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        sodium_memzero($key);
        return $plaintext === false ? null : $plaintext;
    }
}
