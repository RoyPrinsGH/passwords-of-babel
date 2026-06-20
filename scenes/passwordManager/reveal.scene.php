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
    private BabelLocation $decryptedPasswordLocation;

    public function __construct(
        private int $passwordIndex,
    ) {
        $password = PasswordsOfBabelData::get()->encryptedPasswords[$passwordIndex];
        $this->passwordName = $password->name;
        $this->decryptedPasswordLocation = BabelLocation::fromString(EncryptionKey::get()->decrypt($password->encryptedLocation));
        copyEnormousTextToClipboard($this->decryptedPasswordLocation->room);
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

        $modalCentreX = $dimensions->width / 3 + ($modalWidth / 2);

        $revealText = "[" . $this->passwordName . "]";
        $revealTextX = $modalCentreX - strlen($revealText) / 2;
        $revealTextY = $modalMarginY + 1 + ($modalHeight / 2) - 4;
        Terminal::setColor(Colour::BLUE);
        Terminal::writeAt($revealTextY, $revealTextX, $revealText);

        $lines = [
            "wall = " . $this->decryptedPasswordLocation->wall,
            "shelf = " . $this->decryptedPasswordLocation->shelf,
            "book = " . $this->decryptedPasswordLocation->book,
            "page = " . $this->decryptedPasswordLocation->page,
            "location = line "
                . $this->decryptedPasswordLocation->fromLine + 1 . " character "
                . $this->decryptedPasswordLocation->fromLineIndex . " ~ line "
                . $this->decryptedPasswordLocation->untilLine + 1 . " character "
                . $this->decryptedPasswordLocation->untilLineIndex,
        ];

        foreach ($lines as $index => $line) {
            $x = $modalCentreX - strlen($line) / 2;
            Terminal::writeAt($revealTextY + 1 + $index, $x, $line);
        }

        $copiedText = "[The room has been copied to your clipboard]";
        $copiedTextX = $modalCentreX - strlen($copiedText) / 2;
        $copiedTextY = $modalMarginY + 1 + $modalHeight - 1;
        Terminal::setColor(Colour::GREEN);
        Terminal::writeAt($copiedTextY, $copiedTextX, $copiedText);

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
