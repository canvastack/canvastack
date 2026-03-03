<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Traits;

/**
 * Handles Flash Messages Trait.
 *
 * Provides flash message helpers for controllers.
 */
trait HandlesFlashMessages
{
    /**
     * Flash a success message.
     *
     * @param string $message
     * @return void
     */
    protected function flashSuccess(string $message): void
    {
        session()->flash('success', $message);
    }

    /**
     * Flash an error message.
     *
     * @param string $message
     * @return void
     */
    protected function flashError(string $message): void
    {
        session()->flash('error', $message);
    }

    /**
     * Flash a warning message.
     *
     * @param string $message
     * @return void
     */
    protected function flashWarning(string $message): void
    {
        session()->flash('warning', $message);
    }

    /**
     * Flash an info message.
     *
     * @param string $message
     * @return void
     */
    protected function flashInfo(string $message): void
    {
        session()->flash('info', $message);
    }

    /**
     * Flash multiple messages.
     *
     * @param array<string, string> $messages
     * @return void
     */
    protected function flashMessages(array $messages): void
    {
        foreach ($messages as $type => $message) {
            session()->flash($type, $message);
        }
    }
}
