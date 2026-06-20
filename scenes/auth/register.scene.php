<?php

use MyPhpTui\{BuiltinEvents, Terminal, Colour, Config, Scene, EventBus, KeyDownHandler, MethodEventHandlers, KeyInfo, KeyKind};

class RegisterScene implements Scene, KeyDownHandler
{
    use MethodEventHandlers;

    private string $inputText = "";

    function draw()
    {
        Terminal::clear();

        $dimensions = Terminal::getDimensions();

        $loginText = "Please provide a password for future logins:";
        $loginTextX = $dimensions->width / 2 - strlen($loginText) / 2;
        $loginTextY = $dimensions->height / 2 - 1;

        Terminal::setColor(Colour::BLUE);
        Terminal::writeAt($loginTextY, $loginTextX, $loginText);

        $inputTextX = $dimensions->width / 2 - strlen($this->inputText) / 2;
        $inputTextY = $dimensions->height / 2;

        Terminal::setColor(Colour::GREEN);
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
                $this->submitPasswordChange();
                EventBus::emit(BuiltinEvents::SCENE_SWAP, [LoginScene::class]);
                return;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $inputChar = $keyInfo->data;

                $this->inputText .= $inputChar;
                return;
        }
    }

    function submitPasswordChange()
    {
        $input = trim($this->inputText);
        PasswordsOfBabelData::update(fn($data) => $data->passwordHash = password_hash($input, PASSWORD_BCRYPT));
    }
}
