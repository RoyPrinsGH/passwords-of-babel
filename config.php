<?php

use MyPhpTui\{Config, StorageApi};

const CONFIG_PATH = __DIR__ . "/babel.json";

class PasswordsOfBabelConfig
{
    public function __construct(
        public string $passwordHash,
        public array $encryptedPasswords,
    ) {
        $this->encryptedPasswords = array_map(
            static fn(array $rawArray) => new StoredPassword(...$rawArray),
            $this->encryptedPasswords,
        );
    }
}

$config = StorageApi::load(CONFIG_PATH, PasswordsOfBabelConfig::class);

if ($config === null) {
    $config = new PasswordsOfBabelConfig(
        passwordHash: "",
        encryptedPasswords: []
    );

    StorageApi::store(CONFIG_PATH, $config);
}

Config::set($config);

unset($config);
