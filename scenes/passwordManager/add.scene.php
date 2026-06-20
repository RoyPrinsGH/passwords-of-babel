<?php

use MyPhpTui\{BuiltinEvents, Colour, Config, Terminal, Scene, EventBus, KeyDownHandler, KeyInfo, KeyKind, MethodEventHandlers, StorageApi};

class AddPasswordScene
implements
    Scene,
    KeyDownHandler
{
    use MethodEventHandlers;

    public const EVENT_PASSWORD_UPDATED = 'passwords.updated';

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

    function onKeyDown(KeyInfo $keyInfo)
    {
        if ($this->nameLockedIn) {
            $input = &$this->passwordValueInputText;
        } else {
            $input = &$this->passwordNameInputText;
        }

        switch ($keyInfo->kind) {
            case KeyKind::Escape:
                EventBus::emit(BuiltinEvents::SCENE_POP);
                return;

            case KeyKind::BackSpace:
                $input = substr($input, 0, -1) ?: "";
                return;

            case KeyKind::Enter:
                $this->submitInput();
                return;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $inputChar = $keyInfo->data;

                $input .= $inputChar;
                return;
        }
    }

    function submitInput()
    {
        if (!$this->nameLockedIn) {
            $this->nameLockedIn = true;
            return;
        }

        $this->storePassword();

        EventBus::emit(BuiltinEvents::SCENE_POP);
    }

    function storePassword()
    {
        $config = Config::get();

        $config->encryptedPasswords[]
            = new StoredPassword(
                $this->passwordNameInputText,
                encrypt_string($this->passwordValueInputText, EncryptionKey::get())
            );

        StorageApi::store(CONFIG_PATH, $config);

        EventBus::emit(self::EVENT_PASSWORD_UPDATED);
    }
}
