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

class RevealPasswordScene
implements
    Scene,
    KeyDownHandler
{
    use MethodEventHandlers;

    private string $passwordName;
    private string $decryptedPassword;

    public function __construct(
        private int $passwordIndex,
    ) {
        $password = PasswordsOfBabelData::get()->encryptedPasswords[$passwordIndex];
        $this->passwordName = $password->name;
        $this->decryptedPassword = EncryptionKey::get()->decrypt($password->encryptedValue);
    }

    function draw()
    {
        $dimensions = Terminal::getDimensions();

        $modalMarginX = 2;
        $modalMarginY = 2;
        $modalWidth = 2 * $dimensions->width / 3 - 2 * $modalMarginX;
        $modalHeight = $dimensions->height - 2 * $modalMarginY;
        Terminal::setColor(Colour::GREEN);
        Terminal::drawBorder($modalMarginY + 1, $dimensions->width / 3, $modalWidth, $modalHeight);

        $revealText = "[" . $this->passwordName . "]";
        $revealTextX = $dimensions->width / 3 + ($modalWidth / 2) - strlen($revealText) / 2;
        $revealTextY = $modalMarginY + 1 + ($modalHeight / 2) - 1;
        Terminal::setColor(Colour::BLUE);
        Terminal::writeAt($revealTextY, $revealTextX, $revealText);

        $valueTextX = $dimensions->width / 3 + ($modalWidth / 2) - strlen($this->decryptedPassword) / 2;
        $valueTextY = $modalMarginY + 1 + ($modalHeight / 2);
        Terminal::setColor(Colour::BLACK);
        Terminal::writeAt($valueTextY, $valueTextX, $this->decryptedPassword);

        Terminal::reset();
    }

    function onKeyDown(KeyInfo $keyInfo)
    {
        switch ($keyInfo->kind) {
            case KeyKind::Escape:
                EventBus::emit(BuiltinEvents::SCENE_POP);
                return;
        }
    }
}
