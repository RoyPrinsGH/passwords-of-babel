<?php

use MyPhpTui\Persistable;

final class PasswordsOfBabelData extends Persistable
{
    protected static string $path = __DIR__ . "/babel.json";

    public function __construct(
        public string $passwordHash,
        public array $encryptedPasswords,
    ) {
        $this->encryptedPasswords = array_map(
            static fn(array $rawArray) => new StoredPassword(...$rawArray),
            $this->encryptedPasswords,
        );
    }

    public static function default(): static
    {
        return new PasswordsOfBabelData(
            passwordHash: "",
            encryptedPasswords: []
        );
    }
}

PasswordsOfBabelData::load();
