<?php

namespace MyPhpTui;

use Exception;
use ReflectionClass;

interface Scene
{
    public function draw();
    public function handleEvent(Event $event): ?TuiCallbackAction;
}

class SceneStack
{
    public array $scenes = [];

    public function getTopScene(): ?Scene
    {
        return array_last($this->scenes);
    }

    public function pushScene(Scene $scene)
    {
        array_push($this->scenes, $scene);
    }

    public function popScene()
    {
        array_pop($this->scenes);
    }
}

class Colour
{
    public const BLACK = 30;
    public const RED = 31;
    public const GREEN = 32;
    public const YELLOW = 33;
    public const BLUE = 34;
    public const MAGENTA = 35;
    public const CYAN = 36;
    public const WHITE = 37;
    public const BG_BLACK = 40;
    public const BG_RED = 41;
    public const BG_GREEN = 42;
    public const BG_YELLOW = 43;
    public const BG_BLUE = 44;
    public const BG_MAGENTA = 45;
    public const BG_CYAN = 46;
    public const BG_WHITE = 47;
}

class Terminal
{
    static function clear(): void
    {
        echo "\033[2J\033[H";
    }

    static function moveTo(int $row, int $col): void
    {
        echo "\033[{$row};{$col}H";
    }

    static function setColor(?int $fg = null, ?int $bg = null): void
    {
        $codes = [];
        if ($fg !== null) {
            $codes[] = $fg;
        }
        if ($bg !== null) {
            $codes[] = $bg;
        }
        if ($codes === []) {
            self::reset();
        } else {
            echo "\033[" . implode(';', $codes) . "m";
        }
    }

    static function reset(): void
    {
        echo "\033[0m";
    }

    static function writeAt(int $row, int $col, string $text): void
    {
        self::moveTo($row, $col);
        echo $text;
    }
}

class EventFactory
{
    public static function preKeyHandle(): Event
    {
        return new Event(EventKind::PreKeyHandle, null);
    }

    public static function postKeyHandle(): Event
    {
        return new Event(EventKind::PostKeyHandle, null);
    }

    public static function keyDown(KeyInfo $keyInfo): Event
    {
        return new Event(EventKind::KeyDown, $keyInfo);
    }
}

class Event
{
    public function __construct(
        public EventKind $kind,
        public mixed $data,
    ) {}
}

enum EventKind
{
    case PreKeyHandle;
    case KeyDown;
    case PostKeyHandle;
}

class KeyInfo
{
    public function __construct(
        public KeyKind $kind,
        public mixed $data,
    ) {}
}

enum KeyKind
{
    case Escape;
    case Direction;
    case Character;
    case Unknown;
}

enum Direction
{
    case Up;
    case Down;
    case Right;
    case Left;
}

class TuiCallbackActionFactory
{
    public static function exit(): TuiCallbackAction
    {
        return new TuiCallbackAction(TuiCallbackActionKind::Exit, null);
    }

    public static function pushScene(Scene $scene): TuiCallbackAction
    {
        return new TuiCallbackAction(TuiCallbackActionKind::PushScene, $scene);
    }
}

class TuiCallbackAction
{
    public function __construct(
        public TuiCallbackActionKind $kind,
        public mixed $data,
    ) {}
}

enum TuiCallbackActionKind
{
    case Exit;
    case PushScene;
    case PopScene;
}

class TuiExitException extends Exception {}

function runTui(string $sceneClass)
{
    function readKey(): ?KeyInfo
    {
        $readByte = fn() => (($tc = fread(STDIN, 1)) === '' || $tc === false) ? null : $tc;
        if (($char = $readByte()) === null) return null;
        if ($char !== "\033") return new KeyInfo(KeyKind::Character, $char);
        return new KeyInfo(...match ("\033" .  ($readByte() ?? '') . ($readByte() ?? '')) {
            "\033" => [KeyKind::Escape, null],
            "\033[A" => [KeyKind::Direction, Direction::Up],
            "\033[B" => [KeyKind::Direction, Direction::Down],
            "\033[C" => [KeyKind::Direction, Direction::Right],
            "\033[D" => [KeyKind::Direction, Direction::Left],
            default => [KeyKind::Unknown, null],
        });
    }
    if (!(($rf = new ReflectionClass($sceneClass))->implementsInterface(Scene::class)))
        throw new Exception("Given class is not a Scene");
    if (($c = $rf->getConstructor()) !== null && $c->getNumberOfRequiredParameters() > 0)
        throw new Exception("Given class has a non-trivial constructor");
    ($ss = new SceneStack())->pushScene($rf->newInstance());
    $as = $ss->getTopScene();
    $he = function (Event $event) use ($ss, $as) {
        if (!($callbackAction = $as->handleEvent($event))) return;
        switch ($callbackAction->kind) {
            case TuiCallbackActionKind::Exit:
                throw new TuiExitException();
            case TuiCallbackActionKind::PopScene:
                return $ss->popScene();
            case TuiCallbackActionKind::PushScene:
                assert($callbackAction->data instanceof Scene);
                return $ss->pushScene($callbackAction->data);
        }
    };
    system('stty -icanon -echo min 0 time 1');
    stream_set_blocking(STDIN, false);
    echo "\033[?25l";
    try {
        while (true) {
            ($as = $ss->getTopScene())->draw();
            $he(EventFactory::preKeyHandle());
            while ($ki = readKey()) $he(EventFactory::keyDown($ki));
            $he(EventFactory::postKeyHandle());
            usleep(16_000);
        }
    } catch (TuiExitException) {
    } finally {
        echo "\033[?25h\033[0m\033[2J\033[H";
        system('stty sane');
    }
}
