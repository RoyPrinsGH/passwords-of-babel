<?php

use MyPhpTui\BuiltinEvents;
use MyPhpTui\Colour;
use MyPhpTui\EventBus;
use MyPhpTui\KeyDownHandler;
use MyPhpTui\KeyInfo;
use MyPhpTui\KeyKind;
use MyPhpTui\MethodEventHandlers;
use MyPhpTui\Scene;
use MyPhpTui\Terminal;

class LoginScene implements Scene, KeyDownHandler
{
    use MethodEventHandlers;

    private string $inputText = "";

    function draw()
    {
        Terminal::clear();

        $dimensions = Terminal::getDimensions();

        $loginText = "Please input your password to log in:";
        $loginTextX = $dimensions->width / 2 - strlen($loginText) / 2;
        $loginTextY = $dimensions->height / 2 - 1;

        Terminal::setColor(Colour::GREEN);
        Terminal::writeAt($loginTextY, $loginTextX, $loginText);

        $inputTextX = $dimensions->width / 2 - strlen($this->inputText) / 2;
        $inputTextY = $dimensions->height / 2;

        Terminal::setColor(Colour::RED);
        Terminal::writeAt($inputTextY, $inputTextX, $this->inputText);

        Terminal::reset();
    }

    function onKeyDown(KeyInfo $keyInfo)
    {
        switch ($keyInfo->kind) {
            case KeyKind::BackSpace:
                $this->inputText = substr($this->inputText, 0, -1) ?: "";
                return;

            case KeyKind::Enter:
                self::trySubmitLogin();
                return;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $inputChar = $keyInfo->data;

                $this->inputText .= $inputChar;
                return;
        }
    }

    function trySubmitLogin()
    {
        $input = trim($this->inputText);
        $requiredHash = PasswordsOfBabelData::get()->passwordHash;

        if (password_verify($input, $requiredHash)) {
            EncryptionKey::set(new EncryptionKey($input));
            EventBus::emit(BuiltinEvents::SCENE_SWAP, [PasswordOverviewScene::class]);
        }

        $this->inputText = "";
    }
}
