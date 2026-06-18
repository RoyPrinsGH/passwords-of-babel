<?php
/// kksidd approved
// @formatter:off
namespace MyPhpTui; use Exception; use ReflectionClass;
class Colour { public const BLACK = 30; public const RED = 31; public const GREEN = 32; public const YELLOW = 33; public const BLUE = 34; public const MAGENTA = 35; public const CYAN = 36; public const WHITE = 37; public const BG_BLACK = 40; public const BG_RED = 41; public const BG_GREEN = 42; public const BG_YELLOW = 43; public const BG_BLUE = 44; public const BG_MAGENTA = 45; public const BG_CYAN = 46; public const BG_WHITE = 47; }
class Dimensions { public function __construct(public int $width, public int $height) {} }
class Event{public function __construct(public EventKind $kind, public mixed $data) {} }
enum EventKind { case PreKeyHandle; case KeyDown; case PostKeyHandle; }
class KeyInfo { public function __construct(public KeyKind $kind, public mixed $data) {} }
enum KeyKind { case Escape; case Direction; case Character; case Unknown; }
enum Direction { case Up; case Down; case Right; case Left; }
interface Scene { 
    public function draw(); 
    public function handleEvent(Event $event): ?TuiCallbackAction;
}
class TuiCallbackActionFactory { 
    public static function exit(): TuiCallbackAction { return new TuiCallbackAction(0, null); }
    public static function pushScene(Scene $scene): TuiCallbackAction { return new TuiCallbackAction(1, $scene); }
    public static function popScene(): TuiCallbackAction { return new TuiCallbackAction(2, null); }
}
class TuiCallbackAction { public function __construct(public int $kind, public mixed $data) {} }
class E1 extends Exception {}
class R1 extends Exception {}
function runTui(string $sceneClass)
{
    $ki = null; 
    $rk = function () use (&$ki) { $readByte = fn() => (($tc = fread(STDIN, 1)) === '' || $tc === false) ? null : $tc; $ki = match ($char = $readByte()) { null => null, "\033" => new KeyInfo(...match (($readByte() ?? '') . ($readByte() ?? '')) { "" => [KeyKind::Escape, null], "[A" => [KeyKind::Direction, Direction::Up], "[B" => [KeyKind::Direction, Direction::Down], "[C" => [KeyKind::Direction, Direction::Right], "[D" => [KeyKind::Direction, Direction::Left], default => [KeyKind::Unknown, null], }), default => new KeyInfo(KeyKind::Character, $char) }; };
    if (!(($rf = new ReflectionClass($sceneClass))->implementsInterface(Scene::class)) || (($c = $rf->getConstructor()) !== null && $c->getNumberOfRequiredParameters() > 0)) throw new R1();
    $ss = [($as = $rf->newInstance())];
    $he = function (Event $event) use ($ss, $as) { if (!(($ca = $as->handleEvent($event)) && (($ck = $ca->kind) || true))) return; if ($ck == 0) throw new E1(); if ($ck == 1) return assert($ca->data instanceof Scene) && array_push($ss, $ca->data); if ($ck == 2) return array_pop($ss); };
    system('stty -icanon -echo min 0 time 1'); stream_set_blocking(STDIN, false); echo "\033[?25l";
    try { while (true) { ($as = array_last($ss))->draw(); $he(new Event(EventKind::PreKeyHandle, null)); while ($rk() || $ki) $he(new Event(EventKind::KeyDown, $ki)); $he(new Event(EventKind::PostKeyHandle, null)); usleep(16_000); } } catch (E1) { } finally { echo "\033[?25h\033[0m\033[2J\033[H"; system('stty sane'); }
}
class Terminal {
    public static function clear(): void { echo "\033[2J\033[H"; }
    public static function moveTo(int $row, int $col): void { echo "\033[{$row};{$col}H"; }
    public static function setColor(?int $fg = null, ?int $bg = null): void { if ($fg === null && $bg === null) echo "\033[0m"; if ($fg !== null && $bg !== null) echo "\033[" . $bg . ';' . $fg . "m"; echo "\033[" . ($fg ?? $bg) . "m"; }
    public static function reset(): void { echo "\033[0m"; }
    public static function writeAt(int $row, int $col, string $text): void { self::moveTo($row, $col); echo $text; }
    public static function getSize(): Dimensions { $matches = [null, 24, 80]; preg_match( '/^(\d+)\s+(\d+)$/', trim((string) shell_exec('stty size < /dev/tty 2>/dev/null')), $matches ); return new Dimensions((int) $matches[1], (int) $matches[2]); }
}