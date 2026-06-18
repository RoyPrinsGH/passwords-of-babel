<?php

require_once __DIR__ . '/myphptui.php';

class AppState
{
    public int $x = 0;
    public int $y = 0;
    public array $passwords = [];
}

$APPSTATE = new AppState();

MyPhpTui\runTui();
