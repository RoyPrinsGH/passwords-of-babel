<?php

use MyPhpTui\{BuiltinEvents, Colour, Scene, EventBus, KeyDownHandler, KeyInfo, KeyKind, MethodEventHandlers, Terminal};

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
