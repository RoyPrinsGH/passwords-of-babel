<?php

use MyPhpTui\{BuiltinEvents, Colour, Config, Terminal, Scene, CustomEventHandler, Dimensions, Direction, EventBus, KeyDownHandler, KeyInfo, KeyKind, MethodEventHandlers, ResizeHandler, StorageApi};

class PasswordOverviewScene
implements
    Scene,
    CustomEventHandler,
    ResizeHandler,
    KeyDownHandler
{
    use MethodEventHandlers;

    private int $pageIndex = 0;
    private int $rowIndex = 0;
    private array $passwords = [];

    public function __construct()
    {
        $this->refreshPasswords();
    }

    function draw()
    {
        Terminal::clear();

        $dimensions = Terminal::getDimensions();

        Terminal::setColor(Colour::BLACK, Colour::BG_WHITE);
        Terminal::writeAt(1, 1, str_pad(" Arrow keys to navigate | 'A' to add | 'ENTER' to reveal | 'D' to delete | 'ESC' to quit", $dimensions->width));
        Terminal::reset();

        if (count($this->passwords) == 0) {
            Terminal::setColor(Colour::WHITE, Colour::BG_RED);
            Terminal::writeAt(3, 1, "No passwords stored yet.");
            Terminal::reset();
            return;
        }

        $passwordsPerPage = max(1, $dimensions->height - 5);

        for ($visualIndex = 0; $visualIndex < $passwordsPerPage; $visualIndex++) {
            Terminal::setColor(Colour::WHITE);

            if ($this->rowIndex == $visualIndex)
                Terminal::setColor(Colour::RED);

            if ($passwordToShow = $this->passwords[$this->pageIndex * $passwordsPerPage + $visualIndex] ?? null)
                Terminal::writeAt(3 + $visualIndex, 1, $passwordToShow);
        }

        Terminal::reset();

        $pageCount = (int) (count($this->passwords) / $passwordsPerPage) + 1;
        Terminal::writeAt($dimensions->height - 1, 1, "Page " . $this->pageIndex + 1 . " / " . $pageCount);
    }

    function onCustomEvent(string $eventName, mixed $eventData)
    {

        if ($eventName === AddPasswordScene::EVENT_PASSWORD_UPDATED) {
            $this->refreshPasswords();
        }
    }

    function onResize(Dimensions $_)
    {
        $this->pageIndex = 0;
        $this->rowIndex = 0;
    }

    function onKeyDown(KeyInfo $keyInfo)
    {
        switch ($keyInfo->kind) {
            case KeyKind::Enter:
                $this->revealCurrent();
                return;

            case KeyKind::Escape:
                EventBus::emit(BuiltinEvents::EXIT);
                return;

            case KeyKind::Direction:
                assert($keyInfo->data instanceof Direction);

                match ($keyInfo->data) {
                    Direction::Up => $this->scrollUp(),
                    Direction::Down => $this->scrollDown(),
                    Direction::Left => $this->pageLeft(),
                    Direction::Right => $this->pageRight(),
                };

                return;

            case KeyKind::Character:
                assert($keyInfo->data instanceof string);
                $pressedKey = $keyInfo->data;

                switch ($pressedKey) {
                    case 'a':
                        EventBus::emit(BuiltinEvents::SCENE_PUSH, AddPasswordScene::class);
                        break;
                    case 'd':
                        $this->deleteCurrent();
                        break;
                }

                return;
        }
    }

    function pageLeft()
    {
        $this->pageIndex = max(0, $this->pageIndex - 1);
    }

    function pageRight()
    {
        $dimensions = Terminal::getDimensions();

        $passwordsPerPage = $dimensions->height - 5;

        $pageCount = (int) (count($this->passwords) / $passwordsPerPage) + 1;

        $this->pageIndex = min($pageCount - 1, $this->pageIndex + 1);

        $passwordsShown = ($this->pageIndex == $pageCount - 1)
            ? (count($this->passwords) % $passwordsPerPage)
            : $passwordsPerPage;

        $this->rowIndex = min($passwordsShown - 1, $this->rowIndex);;
    }

    function scrollUp()
    {
        $this->rowIndex = max(0, $this->rowIndex - 1);
    }

    function scrollDown()
    {
        $dimensions = Terminal::getDimensions();

        $passwordsPerPage = $dimensions->height - 5;

        $pageCount = (int) (count($this->passwords) / $passwordsPerPage) + 1;

        $passwordsShown = ($this->pageIndex == $pageCount - 1)
            ? (count($this->passwords) % $passwordsPerPage)
            : $passwordsPerPage;

        $this->rowIndex = min($passwordsShown - 1, $this->rowIndex + 1);
    }

    function revealCurrent()
    {
        //
    }

    function deleteCurrent()
    {
        $dimensions = Terminal::getDimensions();
        $passwordsPerPage = $dimensions->height - 5;
        $config = Config::get();
        array_splice($config->encryptedPasswords, $this->pageIndex * $passwordsPerPage + $this->rowIndex, 1);
        StorageApi::store(CONFIG_PATH, $config);
        $this->refreshPasswords();
    }

    private function refreshPasswords(): void
    {
        $this->passwords = array_map(
            static fn(StoredPassword $pw) => $pw->name,
            Config::get()->encryptedPasswords
        );
    }
}
