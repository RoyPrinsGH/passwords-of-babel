<?php

use MyPhpTui\{Colour, Terminal, Scene, Event, EventKind, Direction, KeyInfo, KeyKind, TuiCallbackAction, TuiCallbackActionFactory};

class PasswordOverviewScene implements Scene
{
    private int $pageIndex = 0;
    private int $rowIndex = 0;
    private array $passwords = ["test1", "test2"];

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
        if ($event->kind === EventKind::View) {
            $this->pageIndex = 0;
            $this->rowIndex = 0;
            goto noAction;
        }

        if ($event->kind !== EventKind::KeyDown)
            goto noAction;

        assert($event->data instanceof KeyInfo);
        $keyInfo = $event->data;

        switch ($keyInfo->kind) {
            case KeyKind::Enter:
                $this->revealCurrent();
                goto noAction;

            case KeyKind::Escape:
                goto doExit;

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

                goto noAction;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $pressedKey = $keyInfo->data;

                switch ($pressedKey) {
                    case 'a':
                        return TuiCallbackActionFactory::pushScene(AddPasswordScene::class);
                    case 'd':
                        $this->deleteCurrent();
                }

            default:
                goto noAction;
        }

        noAction:
        return null;
        doExit:
        return TuiCallbackActionFactory::exit();
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
