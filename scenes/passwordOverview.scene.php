<?php

use MyPhpTui\{Terminal, Scene, Event, TuiCallbackAction, TuiCallbackActionFactory};

class PasswordOverviewScene implements Scene
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
