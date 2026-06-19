<?php

use MyPhpTui\{Terminal, Colour, Scene, Event, EventKind, KeyInfo, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class LoginScene implements Scene
{
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

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        if ($event->kind != EventKind::KeyDown)
            return null;

        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;

        if ($keyInfo->kind === KeyKind::BackSpace) {
            $this->inputText = substr($this->inputText, 0, -1) ?: "";
            return null;
        }

        if ($keyInfo->kind === KeyKind::Enter) {
            if ($this->submitLogin()) {
                global $KEY;
                $KEY = $this->inputText;
                $this->inputText = "";
                return TuiCallbackActionFactory::pushScene(PasswordOverviewScene::class);
            }

            $this->inputText = "";
            return null;
        }

        if ($keyInfo->kind !== KeyKind::Character)
            return null;

        assert($keyInfo->data instanceof string);
        $inputChar = $keyInfo->data;

        $this->inputText .= $inputChar;
        return null;
    }

    function submitLogin(): bool
    {
        global $CONFIG;
        assert($CONFIG instanceof PasswordsOfBabelConfig);
        return password_verify($this->inputText, $CONFIG->passwordHash);
    }
}
