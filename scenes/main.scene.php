<?php

use MyPhpTui\{Terminal, Scene, Event, EventKind, TuiCallbackAction, TuiCallbackActionFactory};

class MainScene implements Scene
{
    function draw()
    {
        Terminal::clear();
    }

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        if ($event->kind !== EventKind::PostKeyHandle)
            return null;

        global $CONFIG;
        assert($CONFIG instanceof PasswordsOfBabelConfig);

        if ($CONFIG->passwordHash === "")
            return TuiCallbackActionFactory::pushScene(SetLoginScene::class);

        return TuiCallbackActionFactory::pushScene(LoginScene::class);
    }
}
