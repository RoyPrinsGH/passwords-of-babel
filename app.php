<?php

require_once __DIR__ . '/myphptui.php';

class PasswordsOfBabelConfig
{
    public function __construct(
        public string $passwordHash,
        public string $connectionString,
    ) {}
}

$CONFIGPATH = __DIR__ . "/babel.json";
$CONFIG = MyPhpTui\StorageApi::load($CONFIGPATH, PasswordsOfBabelConfig::class);

if ($CONFIG === null) {
    $CONFIG = new PasswordsOfBabelConfig(
        passwordHash: "",
        connectionString: "test"
    );

    MyPhpTui\StorageApi::store($CONFIGPATH, $CONFIG);
}

MyPhpTui\runTui();
