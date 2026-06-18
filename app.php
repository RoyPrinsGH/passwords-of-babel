<?php

require_once __DIR__ . '/myphptui.php';

class PasswordsOfBabelConfig
{
    public function __construct(
        public string $passwordHash,
        public string $connectionString,
    ) {}
}

$CONFIG = MyPhpTui\StorageApi::load(__DIR__ . "/babel.json", PasswordsOfBabelConfig::class);

if ($CONFIG === null) {
    $CONFIG = new PasswordsOfBabelConfig(
        passwordHash: password_hash("changeme!", PASSWORD_BCRYPT),
        connectionString: "test"
    );

    MyPhpTui\StorageApi::store(__DIR__ . "/babel.json", $CONFIG);
}

MyPhpTui\runTui();
