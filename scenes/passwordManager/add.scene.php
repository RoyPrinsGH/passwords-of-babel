<?php

use MyPhpTui\{Colour, Terminal, Scene, Event, EventKind, KeyInfo, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class AddPasswordScene implements Scene
{
    private string $passwordNameInputText = "";
    private string $passwordValueInputText = "";
    private bool $nameLockedIn = false;

    function draw()
    {
        Terminal::clear();

        $dimensions = Terminal::getDimensions();

        $nameText = "Please give your password a name:";
        $nameTextX = $dimensions->width / 2 - strlen($nameText) / 2;
        $nameTextY = $dimensions->height / 2 - ($this->nameLockedIn ? 3 : 1);

        Terminal::setColor(Colour::GREEN);
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
            goto noAction;

        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;

        if ($this->nameLockedIn) {
            $input = &$this->passwordValueInputText;
        } else {
            $input = &$this->passwordNameInputText;
        }

        if ($keyInfo->kind === KeyKind::BackSpace) {
            $input = substr($input, 0, -1) ?: "";
            goto noAction;
        }

        if ($keyInfo->kind === KeyKind::Enter) {
            if ($this->nameLockedIn) {
                $this->addPassword();
                return TuiCallbackActionFactory::popScene();
            }

            $this->nameLockedIn = true;

            goto noAction;
        }

        if ($keyInfo->kind !== KeyKind::Character)
            goto noAction;

        assert($keyInfo->data instanceof string);
        $inputChar = $keyInfo->data;

        $input .= $inputChar;

        noAction:
        return null;
    }

    function addPassword()
    {
        //
    }
}
