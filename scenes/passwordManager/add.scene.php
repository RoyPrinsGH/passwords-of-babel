<?php

use MyPhpTui\{Colour, Terminal, Scene, Event, EventKind, KeyInfo, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class AddPasswordScene implements Scene
{
    private string $passwordNameInputText = "";
    private string $passwordValueInputText = "";
    private bool $nameLockedIn = false;

    function draw()
    {
        $dimensions = Terminal::getDimensions();

        $modalMarginX = 20;
        $modalMarginY = 5;
        $modalWidth = $dimensions->width - 2 * $modalMarginX;
        $modalHeight = $dimensions->height - 2 * $modalMarginY;
        Terminal::setColor(Colour::GREEN);
        Terminal::drawRect($modalMarginY, $modalMarginX, $modalWidth, $modalHeight);
        Terminal::drawBorder($modalMarginY, $modalMarginX, $modalWidth, $modalHeight);

        $nameText = "Please give your password a name:";
        $nameTextX = $dimensions->width / 2 - strlen($nameText) / 2;
        $nameTextY = $dimensions->height / 2 - ($this->nameLockedIn ? 3 : 1);

        Terminal::writeAt($nameTextY, $nameTextX, $nameText);

        $passwordNameInputTextX = $dimensions->width / 2 - strlen($this->passwordNameInputText) / 2;
        $passwordNameInputTextY = $dimensions->height / 2 - ($this->nameLockedIn ? 2 : 0);

        if ($this->nameLockedIn) {
            Terminal::setColor(Colour::WHITE);
        } else {
            Terminal::setColor(Colour::RED);
        }

        Terminal::writeAt($passwordNameInputTextY, $passwordNameInputTextX, $this->passwordNameInputText);

        if (!$this->nameLockedIn)
            goto finishDraw;

        $passwordText = "Please fill in this password:";
        $passwordTextX = $dimensions->width / 2 - strlen($passwordText) / 2;
        $passwordTextY = $dimensions->height / 2 + 1;

        Terminal::setColor(Colour::GREEN);
        Terminal::writeAt($passwordTextY, $passwordTextX, $passwordText);

        $passwordValueInputTextX = $dimensions->width / 2 - strlen($this->passwordValueInputText) / 2;
        $passwordValueInputTextY = $dimensions->height / 2 + 2;

        Terminal::setColor(Colour::RED);
        Terminal::writeAt($passwordValueInputTextY, $passwordValueInputTextX, $this->passwordValueInputText);

        finishDraw:
        Terminal::reset();
    }

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        if ($event->kind != EventKind::KeyDown)
            return null;

        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;

        if ($this->nameLockedIn) {
            $input = &$this->passwordValueInputText;
        } else {
            $input = &$this->passwordNameInputText;
        }

        if ($keyInfo->kind === KeyKind::BackSpace) {
            $input = substr($input, 0, -1) ?: "";
            return null;
        }

        if ($keyInfo->kind === KeyKind::Enter) {
            if ($this->nameLockedIn) {
                $this->addPassword();
                return TuiCallbackActionFactory::popScene();
            }

            $this->nameLockedIn = true;
            return null;
        }

        if ($keyInfo->kind !== KeyKind::Character)
            return null;

        assert($keyInfo->data instanceof string);
        $inputChar = $keyInfo->data;

        $input .= $inputChar;
        return null;
    }

    function addPassword()
    {
        global $CONFIG, $CONFIGPATH, $KEY;
        assert($CONFIG instanceof PasswordsOfBabelConfig);
        assert($KEY instanceof string);

        $CONFIG->encryptedPasswords[]
            = new StoredPassword(
                $this->passwordNameInputText,
                encrypt_string($this->passwordValueInputText, $KEY)
            );

        MyPhpTui\StorageApi::store($CONFIGPATH, $CONFIG);
    }
}
