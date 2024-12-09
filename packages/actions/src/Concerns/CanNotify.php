<?php

namespace Filament\Actions\Concerns;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\Response;

trait CanNotify
{
    protected Notification | Closure | null $failureNotification = null;

    protected Notification | Closure | null $successNotification = null;

    protected Notification | Closure | null $unauthorizedNotification = null;

    protected string | Closure | null $failureNotificationTitle = null;

    protected string | Closure | null $successNotificationTitle = null;

    protected string | Closure | null $unauthorizedNotificationTitle = null;

    public function sendFailureNotification(): static
    {
        $notification = $this->evaluate($this->failureNotification, [
            'notification' => $notification = Notification::make()
                ->danger()
                ->title($this->getFailureNotificationTitle()),
        ]) ?? $notification;

        if (filled($notification?->getTitle())) {
            $notification->send();
        }

        return $this;
    }

    public function failureNotification(Notification | Closure | null $notification): static
    {
        $this->failureNotification = $notification;

        return $this;
    }

    /**
     * @deprecated Use `failureNotificationTitle()` instead.
     */
    public function failureNotificationMessage(string | Closure | null $message): static
    {
        return $this->failureNotificationTitle($message);
    }

    public function failureNotificationTitle(string | Closure | null $title): static
    {
        $this->failureNotificationTitle = $title;

        return $this;
    }

    public function sendSuccessNotification(): static
    {
        $notification = $this->evaluate($this->successNotification, [
            'notification' => $notification = Notification::make()
                ->success()
                ->title($this->getSuccessNotificationTitle()),
        ]) ?? $notification;

        if (filled($notification?->getTitle())) {
            $notification->send();
        }

        return $this;
    }

    public function successNotification(Notification | Closure | null $notification): static
    {
        $this->successNotification = $notification;

        return $this;
    }

    /**
     * @deprecated Use `successNotificationTitle()` instead.
     */
    public function successNotificationMessage(string | Closure | null $message): static
    {
        return $this->successNotificationTitle($message);
    }

    public function successNotificationTitle(string | Closure | null $title): static
    {
        $this->successNotificationTitle = $title;

        return $this;
    }

    public function sendUnauthorizedNotification(Response $response): static
    {
        $notification = $this->evaluate($this->unauthorizedNotification, [
            'notification' => $notification = Notification::make()
                ->danger()
                ->title($this->getUnauthorizedNotificationTitle() ?? $response->message()),
        ]) ?? $notification;

        if (filled($notification?->getTitle())) {
            $notification->send();
        }

        return $this;
    }

    public function unauthorizedNotification(Notification | Closure | null $notification): static
    {
        $this->unauthorizedNotification = $notification;

        return $this;
    }

    public function unauthorizedNotificationTitle(string | Closure | null $title): static
    {
        $this->unauthorizedNotificationTitle = $title;

        return $this;
    }

    public function getSuccessNotificationTitle(): ?string
    {
        return $this->evaluate($this->successNotificationTitle);
    }

    public function getFailureNotificationTitle(): ?string
    {
        return $this->evaluate($this->failureNotificationTitle);
    }

    public function getUnauthorizedNotificationTitle(): ?string
    {
        return $this->evaluate($this->unauthorizedNotificationTitle);
    }
}
