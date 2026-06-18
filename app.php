<?php

require_once __DIR__ . '/myphptui.php';
require_once __DIR__ . '/crypto.php';

class PasswordsOfBabelConfig
{
    public function __construct(
        public string $passwordHash,
        public array $encryptedPasswords,
    ) {}
}

$CONFIGPATH = __DIR__ . "/babel.json";
$CONFIG = MyPhpTui\StorageApi::load($CONFIGPATH, PasswordsOfBabelConfig::class);

if ($CONFIG === null) {
    $CONFIG = new PasswordsOfBabelConfig(
        passwordHash: "",
        encryptedPasswords: []
    );

    MyPhpTui\StorageApi::store($CONFIGPATH, $CONFIG);
}

MyPhpTui\runTui();
