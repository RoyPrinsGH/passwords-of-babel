<?php

use MyPhpTui\{Terminal, Scene, Event, TuiCallbackAction, TuiCallbackActionFactory};

class PasswordsOverviewScene implements Scene
{
    function draw()
    {
        Terminal::clear();
        echo "lol";
    }

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        return null;
    }
}
