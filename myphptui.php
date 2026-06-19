<?php

namespace MyPhpTui;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class Colour
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

final class Dimensions
{
    public function __construct(
        public int $width,
        public int $height,
    ) {}
}

final class Event
{
    public function __construct(
        public EventKind $kind,
        public mixed $data,
    ) {}
}

final class EventFactory
{
    public static function preKeyHandle(): Event
    {
        return new Event(EventKind::PreKeyHandle, null);
    }

    public static function keyDown(KeyInfo $keyInfo): Event
    {
        return new Event(EventKind::KeyDown, $keyInfo);
    }

    public static function postKeyHandle(): Event
    {
        return new Event(EventKind::PostKeyHandle, null);
    }

    public static function view(): Event
    {
        return new Event(EventKind::View, null);
    }

    public static function unView(): Event
    {
        return new Event(EventKind::UnView, null);
    }
}

enum EventKind
{
    case PreKeyHandle;
    case KeyDown;
    case PostKeyHandle;
    case View;
    case UnView;
}

final class KeyInfo
{
    public function __construct(
        public KeyKind $kind,
        public mixed $data,
    ) {}
}

enum KeyKind
{
    case Escape;
    case BackSpace;
    case Enter;
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

interface Scene
{
    public function draw();

    public function handleEvent(Event $event): ?TuiCallbackAction;
}

final class SceneManager
{
    private array $scenes;

    public function __construct(string $initialSceneClass)
    {
        $this->scenes = [self::instantiateScene($initialSceneClass)];
    }

    public function pushScene(string $sceneClass): void
    {
        $this->scenes[] = self::instantiateScene($sceneClass);
    }

    public function popScene(): void
    {
        array_pop($this->scenes);
    }

    public function swapScene(string $sceneClass): void
    {
        $this->popScene();
        $this->pushScene($sceneClass);
    }

    public function getTopScene(): Scene
    {
        return array_last($this->scenes)
            ?? throw new EmptySceneStackException();
    }

    private static function instantiateScene(string $sceneClass): Scene
    {
        $class = new ReflectionClass($sceneClass);

        if (!$class->implementsInterface(Scene::class))
            throw new InvalidSceneClassException();

        if (!($class->getConstructor()?->getNumberOfRequiredParameters() ?? 0 === 0))
            throw new InvalidSceneClassException();

        return $class->newInstance();
    }
}

enum SceneActionKind
{
    case PushScene;
    case PopScene;
    case SwapScene;
}

final class SceneAction
{
    public function __construct(
        public SceneActionKind $kind,
        public mixed $data,
    ) {}
}

enum TuiCallbackActionKind
{
    case Exit;
    case SceneAction;
}

final class TuiCallbackActionFactory
{
    public static function exit(): TuiCallbackAction
    {
        return new TuiCallbackAction(TuiCallbackActionKind::Exit, null);
    }

    public static function pushScene(string $sceneClass): TuiCallbackAction
    {
        return new TuiCallbackAction(
            TuiCallbackActionKind::SceneAction,
            new SceneAction(SceneActionKind::PushScene, $sceneClass),
        );
    }

    public static function swapScene(string $sceneClass): TuiCallbackAction
    {
        return new TuiCallbackAction(
            TuiCallbackActionKind::SceneAction,
            new SceneAction(SceneActionKind::SwapScene, $sceneClass),
        );
    }

    public static function popScene(): TuiCallbackAction
    {
        return new TuiCallbackAction(
            TuiCallbackActionKind::SceneAction,
            new SceneAction(SceneActionKind::PopScene, null),
        );
    }
}

final class TuiCallbackAction
{
    public function __construct(
        public TuiCallbackActionKind $kind,
        public mixed $data,
    ) {}
}

final class TuiExitException extends Exception {}

final class EmptySceneStackException extends Exception {}

final class InvalidSceneClassException extends Exception {}

function readKey(): ?KeyInfo
{
    $readByte = static function (): ?string {
        $char = fread(STDIN, 1);
        return ($char === '' || $char === false)
            ? null
            : $char;
    };

    $char = $readByte();

    if ($char === null)
        return null;

    switch ($char) {
        case "\033":
            $escapeSequence = ($readByte() ?? '') . ($readByte() ?? '');

            switch ($escapeSequence) {
                case '':
                    return new KeyInfo(KeyKind::Escape, null);

                case '[A':
                    return new KeyInfo(KeyKind::Direction, Direction::Up);

                case '[B':
                    return new KeyInfo(KeyKind::Direction, Direction::Down);

                case '[C':
                    return new KeyInfo(KeyKind::Direction, Direction::Right);

                case '[D':
                    return new KeyInfo(KeyKind::Direction, Direction::Left);

                default:
                    return new KeyInfo(KeyKind::Unknown, null);
            }

        case "\n":
        case "\r":
            return new KeyInfo(KeyKind::Enter, null);

        case "\010":
        case "\177":
            return new KeyInfo(KeyKind::BackSpace, null);

        default:
            return new KeyInfo(KeyKind::Character, $char);
    }
}

function loadScenesRecursive()
{
    foreach (
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::FOLLOW_SYMLINKS)
        ) as $file
    ) {
        if (!$file->isFile())
            continue;
        if (str_ends_with($file->getFilename(), '.scene.php'))
            require_once $file->getPathname();
    }
}

function runTui(?string $startSceneClass = null)
{
    loadScenesRecursive();

    $sceneManager = new SceneManager($startSceneClass ?? 'MainScene');

    system('stty -icanon -echo min 0 time 1');
    stream_set_blocking(STDIN, false);
    echo "\033[?25l";

    try {
        $activeScene = $sceneManager->getTopScene();
        $queuedSceneAction = null;

        $handleEvent =
            function (Event $event)
            use (&$activeScene, &$queuedSceneAction) {
                $callbackAction = $activeScene->handleEvent($event);
                unset($event);
                switch ($callbackAction?->kind) {
                    case TuiCallbackActionKind::Exit:
                        throw new TuiExitException();
                    case TuiCallbackActionKind::SceneAction:
                        assert(($queuedSceneAction = $callbackAction->data) instanceof SceneAction);
                }
            };

        while (true) {
            $activeScene->draw();

            $handleEvent(EventFactory::preKeyHandle());

            while ($keyInfo = readKey())
                $handleEvent(EventFactory::keyDown($keyInfo));

            $handleEvent(EventFactory::postKeyHandle());

            if ($queuedSceneAction) {
                $handleEvent(EventFactory::unView());

                switch ($queuedSceneAction->kind) {
                    case SceneActionKind::PushScene:
                        assert(($sceneClass = $queuedSceneAction->data) instanceof string);
                        $sceneManager->pushScene($sceneClass);
                        break;

                    case SceneActionKind::PopScene:
                        $sceneManager->popScene();
                        break;

                    case SceneActionKind::SwapScene:
                        assert(($sceneClass = $queuedSceneAction->data) instanceof string);
                        $sceneManager->swapScene($sceneClass);
                        break;
                }

                $activeScene = $sceneManager->getTopScene();
                $handleEvent(EventFactory::view());

                $queuedSceneAction = null;
            }

            usleep(16_000);
        }
    } catch (TuiExitException | EmptySceneStackException) {
        //
    } finally {
        echo "\033[?25h\033[0m\033[2J\033[H";
        system('stty sane');
    }
}

final class Terminal
{
    public static function clear(): void
    {
        echo "\033[2J\033[H";
    }

    public static function moveTo(int $row, int $col): void
    {
        echo "\033[{$row};{$col}H";
    }

    public static function setColor(?int $fg = null, ?int $bg = null): void
    {
        if ($fg === null && $bg === null) {
            echo "\033[0m";
        }

        if ($fg !== null && $bg !== null) {
            echo "\033[" . $bg . ';' . $fg . "m";
        }

        echo "\033[" . ($fg ?? $bg) . "m";
    }

    public static function reset(): void
    {
        echo "\033[0m";
    }

    public static function writeAt(int $row, int $col, string $text): void
    {
        self::moveTo($row, $col);
        echo $text;
    }

    public static function getDimensions(): Dimensions
    {
        $matches = [null, 24, 80];

        preg_match(
            '/^(\d+)\s+(\d+)$/',
            trim((string) shell_exec('stty size < /dev/tty 2>/dev/null')),
            $matches,
        );

        return new Dimensions((int) $matches[2], (int) $matches[1]);
    }
}

final class CannotReadFileException extends Exception {}

final class CannotWriteDirException extends Exception {}

final class StorageApi
{
    public static function load(string $path, string $configClassName): ?object
    {
        if (!is_readable($path) || !file_exists($path))
            throw new CannotReadFileException();

        if (!($json = file_get_contents($path)))
            throw new CannotReadFileException();

        return new $configClassName(...json_decode($json, true, flags: 4194304));
    }

    public static function store(string $path, object $config): void
    {
        if (!is_dir($dir = dirname($path)) || !is_writable($dir))
            throw new CannotWriteDirException();

        if (!file_put_contents($path, json_encode($config, 128 | 64 | 4194304), 2))
            throw new CannotWriteDirException();
    }
}
