<?php

namespace Filament\Panel\Concerns;

use Closure;
use Filament\Livewire\DatabaseNotifications;

trait HasNotifications
{
    protected bool | Closure $hasDatabaseNotifications = false;

    protected bool | Closure $hasLazyLoadedDatabaseNotifications = true;

    protected string | Closure | null $databaseNotificationsLivewireComponent = null;

    protected string | Closure | null $databaseNotificationsPolling = '30s';

    public function databaseNotifications(bool | Closure $condition = true, string | Closure | null $livewireComponent = null, bool | Closure $isLazy = true): static
    {
        $this->hasDatabaseNotifications = $condition;
        $this->databaseNotificationsLivewireComponent($livewireComponent);
        $this->lazyLoadedDatabaseNotifications($isLazy);

        return $this;
    }

    public function databaseNotificationsLivewireComponent(string | Closure | null $livewireComponent): static
    {
        $this->databaseNotificationsLivewireComponent = $livewireComponent;

        return $this;
    }

    public function lazyLoadedDatabaseNotifications(bool | Closure $condition = true): static
    {
        $this->hasLazyLoadedDatabaseNotifications = $condition;

        return $this;
    }

    public function databaseNotificationsPolling(string | Closure | null $interval): static
    {
        $this->databaseNotificationsPolling = $interval;

        return $this;
    }

    public function hasDatabaseNotifications(): bool
    {
        return (bool) $this->evaluate($this->hasDatabaseNotifications);
    }

    public function hasLazyLoadedDatabaseNotifications(): bool
    {
        return (bool) $this->evaluate($this->hasLazyLoadedDatabaseNotifications);
    }

    public function getDatabaseNotificationsLivewireComponent(): string
    {
        return $this->evaluate($this->databaseNotificationsLivewireComponent) ?? DatabaseNotifications::class;
    }

    public function getDatabaseNotificationsPollingInterval(): ?string
    {
        return $this->evaluate($this->databaseNotificationsPolling);
    }
}
