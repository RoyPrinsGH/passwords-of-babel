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

    function draw()
    {
        $dimensions = Terminal::getDimensions();

        $modalMarginX = 2;
        $modalMarginY = 2;
        $modalWidth = $dimensions->width / 2 - 2 * $modalMarginX;
        $modalHeight = $dimensions->height - 2 * $modalMarginY;
        Terminal::setColor(Colour::GREEN);
        Terminal::drawBorder($modalMarginY + 1, $dimensions->width / 2, $modalWidth, $modalHeight);
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
