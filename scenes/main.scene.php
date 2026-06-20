<?php

use MyPhpTui\{BuiltinEvents, Terminal, Scene, Event, EventBus};

class MainScene implements Scene
{
    function __construct()
    {
        $isPasswordUnset = (PasswordsOfBabelData::get()->passwordHash === "");

        if ($isPasswordUnset) {
            EventBus::emit(BuiltinEvents::SCENE_SWAP, [RegisterScene::class]);
        } else {
            EventBus::emit(BuiltinEvents::SCENE_SWAP, [LoginScene::class]);
        }
    }

    function draw()
    {
        Terminal::clear();
        Terminal::writeAt(0, 0, "Loading...");
    }

    function handleEvent(Event $event)
    {
        //
    }
}
