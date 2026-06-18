<?php

use MyPhpTui\{Terminal, Colour, Scene, Event, EventKind, KeyInfo, Direction, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class MainScene implements Scene
{
    function draw()
    {
        global $APPSTATE;
        Terminal::clear();
        Terminal::setColor(Colour::GREEN);
        Terminal::writeAt($APPSTATE->y, $APPSTATE->x, "@");
        Terminal::reset();
        Terminal::writeAt(20, 1, "Move with arrows or WASD. Press q to quit.");
    }

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        global $APPSTATE;
        if ($event->kind !== EventKind::KeyDown) return null;
        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;
        switch ($keyInfo->kind) {
            case KeyKind::Escape:
                goto exitTui;
            case KeyKind::Direction:
                assert($keyInfo->data instanceof Direction);
                match ($keyInfo->data) {
                    Direction::Up => $APPSTATE->y--,
                    Direction::Down => $APPSTATE->y++,
                    Direction::Left => $APPSTATE->x--,
                    Direction::Right => $APPSTATE->x++,
                    default => null,
                };
                goto update;
            case KeyKind::Character:
                assert($keyInfo->data instanceof string);

                if ($keyInfo->data == 'r')
                    return TuiCallbackActionFactory::pushScene(LoginScene::class);

                match ($keyInfo->data) {
                    'w' => $APPSTATE->y--,
                    's' => $APPSTATE->y++,
                    'a' => $APPSTATE->x--,
                    'd' => $APPSTATE->x++,
                    default => null,
                };
                goto update;
            case KeyKind::Unknown:
            default:
                goto noAction;
        }
        update:
        $APPSTATE->x = max(1, min(60, $APPSTATE->x));
        $APPSTATE->y = max(1, min(18, $APPSTATE->y));
        noAction:
        return null;
        exitTui:
        return TuiCallbackActionFactory::exit();
    }
}
