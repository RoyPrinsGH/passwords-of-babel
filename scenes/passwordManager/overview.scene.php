<?php

use MyPhpTui\BuiltinEvents;
use MyPhpTui\Colour;
use MyPhpTui\CustomEventHandler;
use MyPhpTui\Dimensions;
use MyPhpTui\Direction;
use MyPhpTui\EventBus;
use MyPhpTui\KeyDownHandler;
use MyPhpTui\KeyInfo;
use MyPhpTui\KeyKind;
use MyPhpTui\MethodEventHandlers;
use MyPhpTui\ResizeHandler;
use MyPhpTui\Scene;
use MyPhpTui\Terminal;

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
    private Dimensions $cachedDimensions;
    private int $passwordsPerPage = 1;
    private int $passwordIndexOffset = 0;
    private int $pageCount = 1;

    public function __construct()
    {
        $this->reloadPasswords();
        $this->onResize(Terminal::getDimensions());
    }

    function draw()
    {
        Terminal::clear();
        Terminal::setColor(Colour::BLACK, Colour::BG_WHITE);
        Terminal::writeAt(
            1,
            1,
            str_pad(
                " Arrow keys to navigate | 'A' to add | 'ENTER' to reveal | 'D' to delete | 'ESC' to quit",
                $this->cachedDimensions->width
            )
        );
        Terminal::reset();

        if (count($this->passwords) == 0) {
            Terminal::setColor(Colour::WHITE, Colour::BG_RED);
            Terminal::writeAt(3, 1, "No passwords stored yet.");
            Terminal::reset();
            return;
        }

        for ($visualIndex = 0; $visualIndex < $this->passwordsPerPage; $visualIndex++) {
            Terminal::setColor(Colour::WHITE);

            if ($this->rowIndex == $visualIndex)
                Terminal::setColor(Colour::RED);

            if ($passwordToShow = $this->passwords[$this->passwordIndexOffset + $visualIndex] ?? null)
                Terminal::writeAt(3 + $visualIndex, 1, $passwordToShow);
        }

        Terminal::reset();
        Terminal::writeAt(
            $this->cachedDimensions->height - 1,
            1,
            "Page " . $this->pageIndex + 1 . " / " . $this->pageCount
        );
    }

    function onCustomEvent(string $eventName, mixed $eventData)
    {

        if (
            $eventName === AddPasswordScene::EVENT_PASSWORD_UPDATED
            || $eventName === ConfirmDeletionScene::EVENT_PASSWORD_DELETED
        ) {
            $this->reloadPasswords();
        }
    }

    function onResize(Dimensions $dimensions)
    {
        $this->pageIndex = 0;
        $this->rowIndex = 0;
        $this->cachedDimensions = $dimensions;
        $this->passwordsPerPage = max(1, $this->cachedDimensions->height - 5);
        $this->pageCount = (int) ((count($this->passwords) - 1) / $this->passwordsPerPage) + 1;
        $this->passwordIndexOffset = $this->pageIndex * $this->passwordsPerPage;
    }

    function onKeyDown(KeyInfo $keyInfo)
    {
        switch ($keyInfo->kind) {
            case KeyKind::Enter:
                EventBus::emit(
                    BuiltinEvents::SCENE_PUSH,
                    [RevealPasswordScene::class, [$this->passwordIndexOffset + $this->rowIndex]]
                );
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
                        EventBus::emit(
                            BuiltinEvents::SCENE_PUSH,
                            [AddPasswordScene::class]
                        );
                        break;

                    case 'd':
                        EventBus::emit(
                            BuiltinEvents::SCENE_PUSH,
                            [ConfirmDeletionScene::class, [$this->passwordIndexOffset + $this->rowIndex]]
                        );
                        break;
                }

                return;
        }
    }

    function pageLeft()
    {
        $this->pageIndex = max(0, $this->pageIndex - 1);
        $this->passwordIndexOffset = $this->pageIndex * $this->passwordsPerPage;
    }

    function pageRight()
    {
        $this->pageIndex = min($this->pageCount - 1, $this->pageIndex + 1);
        $this->passwordIndexOffset = $this->pageIndex * $this->passwordsPerPage;

        $passwordsShown = ($this->pageIndex == $this->pageCount - 1)
            ? (count($this->passwords) % $this->passwordsPerPage)
            : $this->passwordsPerPage;

        $this->rowIndex = min($passwordsShown - 1, $this->rowIndex);;
    }

    function scrollUp()
    {
        $this->rowIndex = max(0, $this->rowIndex - 1);
    }

    function scrollDown()
    {
        $passwordsShown = ($this->pageIndex == $this->pageCount - 1)
            ? (count($this->passwords) % $this->passwordsPerPage)
            : $this->passwordsPerPage;

        $this->rowIndex = min($passwordsShown - 1, $this->rowIndex + 1);
    }

    function deleteCurrent()
    {
        PasswordsOfBabelData::update(
            fn($data) => array_splice(
                $data->encryptedPasswords,
                $this->pageIndex * $this->passwordsPerPage + $this->rowIndex,
                1
            )
        );
        $this->reloadPasswords();
    }

    private function reloadPasswords(): void
    {
        $this->passwords = array_map(
            static fn(StoredPassword $pw) => $pw->name,
            PasswordsOfBabelData::get()->encryptedPasswords
        );
        $this->pageCount = (int) ((count($this->passwords) - 1) / $this->passwordsPerPage) + 1;
    }
}
