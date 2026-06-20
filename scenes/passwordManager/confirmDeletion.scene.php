<?php

use MyPhpTui\{BuiltinEvents, Colour, Config, Scene, EventBus, KeyDownHandler, KeyInfo, KeyKind, MethodEventHandlers, StorageApi, Terminal};

class ConfirmDeletionScene
implements
    Scene,
    KeyDownHandler
{
    use MethodEventHandlers;

    public const EVENT_PASSWORD_DELETED = 'passwords.deleted';

    private string $passwordName;

    public function __construct(
        private int $passwordIndex,
    ) {
        $this->passwordName = Config::get()->encryptedPasswords[$passwordIndex]->name;
    }

    function draw()
    {
        $dimensions = Terminal::getDimensions();

        $modalMarginX = 20;
        $modalMarginY = 5;
        $modalWidth = $dimensions->width - 2 * $modalMarginX;
        $modalHeight = $dimensions->height - 2 * $modalMarginY;
        Terminal::setColor(Colour::BLACK, Colour::BG_RED);
        Terminal::drawRect($modalMarginY, $modalMarginX, $modalWidth, $modalHeight);
        Terminal::drawBorder($modalMarginY, $modalMarginX, $modalWidth, $modalHeight);

        $confirmText = "Are you sure you want to delete '" . $this->passwordName . "'?";
        $confirmTextX = $dimensions->width / 2 - strlen($confirmText) / 2;
        $confirmTextY = $dimensions->height / 2 - 1;
        Terminal::writeAt($confirmTextY, $confirmTextX, $confirmText);

        $instructionText = "ENTER = YES | ESC = NO";
        $instructionTextX = $dimensions->width / 2 - strlen($instructionText) / 2;
        $instructionTextY = $dimensions->height / 2 + 1;
        Terminal::writeAt($instructionTextY, $instructionTextX, $instructionText);

        Terminal::reset();
    }

    function onKeyDown(KeyInfo $keyInfo)
    {
        switch ($keyInfo->kind) {
            case KeyKind::Escape:
                EventBus::emit(BuiltinEvents::SCENE_POP);
                return;
            case KeyKind::Enter:
                $this->deleteCurrent();
                EventBus::emit(BuiltinEvents::SCENE_POP);
                return;
        }
    }

    function deleteCurrent()
    {
        $config = Config::get();
        array_splice($config->encryptedPasswords, $this->passwordIndex, 1);
        StorageApi::store(CONFIG_PATH, $config);
        EventBus::emit(self::EVENT_PASSWORD_DELETED);
    }
}
