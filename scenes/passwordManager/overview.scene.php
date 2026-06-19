<?php

use MyPhpTui\{Colour, Terminal, Scene, Event, EventKind, Direction, KeyInfo, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class PasswordOverviewScene implements Scene
{
    private int $pageIndex = 0;
    private int $rowIndex = 0;
    private array $passwords = [];

    public function __construct()
    {
        global $CONFIG;
        assert($CONFIG instanceof PasswordsOfBabelConfig);
        $this->passwords = array_map(static fn(StoredPassword $pw) => $pw->name, $CONFIG->encryptedPasswords);
    }

    function draw()
    {
        Terminal::clear();

        $dimensions = Terminal::getDimensions();

        Terminal::setColor(Colour::BLACK, Colour::BG_WHITE);
        Terminal::writeAt(1, 0, str_pad(" Arrow keys to navigate | 'A' to add | 'ENTER' to reveal | 'D' to delete | 'ESC' to quit", $dimensions->width));
        Terminal::reset();

        if (count($this->passwords) == 0) {
            Terminal::setColor(Colour::WHITE, Colour::BG_RED);
            Terminal::writeAt(3, 0, "No passwords stored yet.");

            goto finishDraw;
        }

        $passwordsPerPage = max(1, $dimensions->height - 4);

        for ($visualIndex = 0; $visualIndex < $passwordsPerPage; $visualIndex++) {
            Terminal::setColor(Colour::WHITE);

            if ($this->rowIndex == $visualIndex)
                Terminal::setColor(Colour::RED);

            if ($passwordToShow = $this->passwords[$this->pageIndex * $passwordsPerPage + $visualIndex] ?? null)
                Terminal::writeAt(3 + $visualIndex, 0, $passwordToShow);
        }

        $pageCount = (int) (count($this->passwords) / $passwordsPerPage) + 1;
        Terminal::writeAt($dimensions->height - 1, 0, "Page " . $this->pageIndex + 1 . " / " . $pageCount);

        finishDraw:
        Terminal::reset();
    }

    function handleEvent(Event $event): ?TuiCallbackAction
    {
        if ($event->kind === EventKind::Resize) {
            $this->pageIndex = 0;
            $this->rowIndex = 0;
            return null;
        }

        if ($event->kind !== EventKind::KeyDown)
            return null;

        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;

        switch ($keyInfo->kind) {
            case KeyKind::Enter:
                $this->revealCurrent();
                break;

            case KeyKind::Escape:
                return TuiCallbackActionFactory::exit();

            case KeyKind::Direction:
                assert($keyInfo->data instanceof Direction);

                $dimensions = Terminal::getDimensions();
                $passwordsPerPage = $dimensions->height - 4;
                $pageCount = count($this->passwords) / $passwordsPerPage + 1;

                match ($keyInfo->data) {
                    Direction::Up => $this->rowIndex = max(0, $this->rowIndex - 1),
                    Direction::Down => $this->rowIndex = min(count($this->passwords) - 1, $this->rowIndex + 1),
                    Direction::Left => $this->pageIndex = max(0, $this->pageIndex - 1),
                    Direction::Right => $this->pageIndex = min($pageCount - 1, $this->pageIndex + 1),
                };

                break;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $pressedKey = $keyInfo->data;

                switch ($pressedKey) {
                    case 'a':
                        return TuiCallbackActionFactory::pushScene(AddPasswordScene::class);
                    case 'd':
                        $this->deleteCurrent();
                }

                break;

            default:
                break;
        }

        return null;
    }

    function revealCurrent()
    {
        //
    }

    function deleteCurrent()
    {
        //
    }
}
