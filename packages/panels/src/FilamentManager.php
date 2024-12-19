<?php

namespace Filament;

use Closure;
use Exception;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Enums\ThemeMode;
use Filament\Events\ServingFilament;
use Filament\Events\TenantSet;
use Filament\Exceptions\NoDefaultPanelSetException;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\MultiFactorAuthentication\Contracts\MultiFactorAuthenticationProvider;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Assets\Theme;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets\Widget;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

class FilamentManager
{
    protected ?string $currentDomain = null;

    protected ?Panel $currentPanel = null;

    protected bool $isServing = false;

    protected bool $isCurrentPanelBooted = false;

    protected ?Model $tenant = null;

    public function __construct()
    {
        // Resolve the panel registry to set the current panel
        // as the default, which uses a `resolving()` callback.
        app()->resolved(PanelRegistry::class) || app(PanelRegistry::class);
    }

    public function auth(): Guard
    {
        return $this->getCurrentPanelOrDefault()->auth();
    }

    public function bootCurrentPanel(): void
    {
        if ($this->isCurrentPanelBooted) {
            return;
        }

        $this->getCurrentPanelOrDefault()->boot();

        $this->isCurrentPanelBooted = true;
    }

    /**
     * @return array<NavigationGroup>
     */
    public function buildNavigation(): array
    {
        return $this->getCurrentPanelOrDefault()->buildNavigation();
    }

    public function getAuthGuard(): string
    {
        return $this->getCurrentPanelOrDefault()->getAuthGuard();
    }

    public function getAuthPasswordBroker(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getAuthPasswordBroker();
    }

    public function getBrandName(): string | Htmlable
    {
        return $this->getCurrentPanelOrDefault()->getBrandName();
    }

    public function getBrandLogo(): string | Htmlable | null
    {
        return $this->getCurrentPanelOrDefault()->getBrandLogo();
    }

    public function getBrandLogoHeight(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getBrandLogoHeight();
    }

    public function getCollapsedSidebarWidth(): string
    {
        return $this->getCurrentPanelOrDefault()->getCollapsedSidebarWidth();
    }

    /**
     * @throws NoDefaultPanelSetException
     */
    public function getCurrentPanelOrDefault(): ?Panel
    {
        return $this->getCurrentPanel() ?? app(PanelRegistry::class)->getDefault();
    }

    public function getCurrentPanel(): ?Panel
    {
        return $this->currentPanel;
    }

    public function getDarkModeBrandLogo(): string | Htmlable | null
    {
        return $this->getCurrentPanelOrDefault()->getDarkModeBrandLogo();
    }

    public function getDatabaseNotificationsPollingInterval(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getDatabaseNotificationsPollingInterval();
    }

    public function getDefaultAvatarProvider(): string
    {
        return $this->getCurrentPanelOrDefault()->getDefaultAvatarProvider();
    }

    /**
     * @throws NoDefaultPanelSetException
     */
    public function getDefaultPanel(): Panel
    {
        return app(PanelRegistry::class)->getDefault();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getEmailVerificationPromptUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getEmailVerificationPromptUrl($parameters);
    }

    public function getEmailVerifiedMiddleware(): string
    {
        return $this->getCurrentPanelOrDefault()->getEmailVerifiedMiddleware();
    }

    public function getFavicon(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getFavicon();
    }

    public function getFontFamily(): string
    {
        return $this->getCurrentPanelOrDefault()->getFontFamily();
    }

    public function getMonoFontFamily(): string
    {
        return $this->getCurrentPanelOrDefault()->getMonoFontFamily();
    }

    public function getSerifFontFamily(): string
    {
        return $this->getCurrentPanelOrDefault()->getSerifFontFamily();
    }

    public function getFontHtml(): Htmlable
    {
        return $this->getCurrentPanelOrDefault()->getFontHtml();
    }

    public function getMonoFontHtml(): Htmlable
    {
        return $this->getCurrentPanelOrDefault()->getMonoFontHtml();
    }

    public function getSerifFontHtml(): Htmlable
    {
        return $this->getCurrentPanelOrDefault()->getSerifFontHtml();
    }

    public function getFontProvider(): string
    {
        return $this->getCurrentPanelOrDefault()->getFontProvider();
    }

    public function getMonoFontProvider(): string
    {
        return $this->getCurrentPanelOrDefault()->getMonoFontProvider();
    }

    public function getSerifFontProvider(): string
    {
        return $this->getCurrentPanelOrDefault()->getSerifFontProvider();
    }

    public function getFontUrl(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getFontUrl();
    }

    public function getMonoFontUrl(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getMonoFontUrl();
    }

    public function getSerifFontUrl(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getSerifFontUrl();
    }

    public function getGlobalSearchDebounce(): string
    {
        return $this->getCurrentPanelOrDefault()->getGlobalSearchDebounce();
    }

    /**
     * @return array<string>
     */
    public function getGlobalSearchKeyBindings(): array
    {
        return $this->getCurrentPanelOrDefault()->getGlobalSearchKeyBindings();
    }

    public function getGlobalSearchFieldSuffix(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getGlobalSearchFieldSuffix();
    }

    public function getGlobalSearchProvider(): ?GlobalSearchProvider
    {
        return $this->getCurrentPanelOrDefault()->getGlobalSearchProvider();
    }

    public function getHomeUrl(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getHomeUrl() ?? $this->getCurrentPanelOrDefault()->getUrl();
    }

    public function getId(): ?string
    {
        return $this->getCurrentPanelOrDefault()?->getId();
    }

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return $this->getCurrentPanelOrDefault()?->getSubNavigationPosition();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getLoginUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getLoginUrl($parameters);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getLogoutUrl(array $parameters = []): string
    {
        return $this->getCurrentPanelOrDefault()->getLogoutUrl($parameters);
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return $this->getCurrentPanelOrDefault()->getMaxContentWidth();
    }

    public function getSimplePageMaxContentWidth(): MaxWidth | string | null
    {
        return $this->getCurrentPanelOrDefault()->getSimplePageMaxContentWidth();
    }

    /**
     * @param  class-string<Model>|Model  $model
     */
    public function getModelResource(string | Model $model): ?string
    {
        return $this->getCurrentPanelOrDefault()->getModelResource($model);
    }

    public function getNameForDefaultAvatar(Model | Authenticatable $record): string
    {
        if ($this->getTenantModel() === $record::class) {
            return $this->getTenantName($record);
        }

        return $this->getUserName($record);
    }

    /**
     * @return array<NavigationGroup>
     */
    public function getNavigation(): array
    {
        return $this->getCurrentPanelOrDefault()->getNavigation();
    }

    /**
     * @return array<string | int, NavigationGroup | string>
     */
    public function getNavigationGroups(): array
    {
        return $this->getCurrentPanelOrDefault()->getNavigationGroups();
    }

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array
    {
        return $this->getCurrentPanelOrDefault()->getNavigationItems();
    }

    /**
     * @return array<string | int, array<class-string> | class-string>
     */
    public function getClusteredComponents(?string $cluster): array
    {
        return $this->getCurrentPanelOrDefault()->getClusteredComponents($cluster);
    }

    /**
     * @return array<class-string>
     */
    public function getPages(): array
    {
        return $this->getCurrentPanelOrDefault()->getPages();
    }

    public function getPanel(?string $id = null, bool $isStrict = true): ?Panel
    {
        return app(PanelRegistry::class)->get($id, $isStrict);
    }

    /**
     * @return array<string, Panel>
     */
    public function getPanels(): array
    {
        return app(PanelRegistry::class)->all();
    }

    public function getPlugin(string $id): Plugin
    {
        return $this->getCurrentPanelOrDefault()->getPlugin($id);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getProfileUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getProfileUrl($parameters);
    }

    public function isProfilePageSimple(): bool
    {
        return $this->getCurrentPanelOrDefault()->isProfilePageSimple();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getRegistrationUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getRegistrationUrl($parameters);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getRequestPasswordResetUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getRequestPasswordResetUrl($parameters);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getResetPasswordUrl(string $token, CanResetPassword | Model | Authenticatable $user, array $parameters = []): string
    {
        return $this->getCurrentPanelOrDefault()->getResetPasswordUrl($token, $user, $parameters);
    }

    /**
     * @return array<class-string>
     */
    public function getResources(): array
    {
        return $this->getCurrentPanelOrDefault()->getResources();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getResourceUrl(string | Model $model, string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?Model $tenant = null): string
    {
        return $this->getCurrentPanelOrDefault()->getResourceUrl($model, $name, $parameters, $isAbsolute, $tenant);
    }

    public function getSidebarWidth(): string
    {
        return $this->getCurrentPanelOrDefault()->getSidebarWidth();
    }

    public function getTenant(): ?Model
    {
        return $this->tenant;
    }

    public function getTenantAvatarUrl(Model $tenant): string
    {
        $avatar = null;

        if ($tenant instanceof HasAvatar) {
            $avatar = $tenant->getFilamentAvatarUrl();
        }

        if ($avatar) {
            return $avatar;
        }

        return app($this->getDefaultAvatarProvider())->get($tenant);
    }

    public function getTenantBillingProvider(): ?Billing\Providers\Contracts\BillingProvider
    {
        return $this->getCurrentPanelOrDefault()->getTenantBillingProvider();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getTenantBillingUrl(array $parameters = [], ?Model $tenant = null): ?string
    {
        return $this->getCurrentPanelOrDefault()->getTenantBillingUrl($tenant ?? $this->getTenant(), $parameters);
    }

    /**
     * @return array<Action>
     */
    public function getTenantMenuItems(): array
    {
        return $this->getCurrentPanelOrDefault()->getTenantMenuItems();
    }

    /**
     * @return class-string<Model>|null
     */
    public function getTenantModel(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getTenantModel();
    }

    public function getTenantName(Model $tenant): string
    {
        if ($tenant instanceof HasName) {
            return $tenant->getFilamentName();
        }

        return $tenant->getAttributeValue('name');
    }

    public function getTenantOwnershipRelationshipName(): string
    {
        return $this->getCurrentPanelOrDefault()->getTenantOwnershipRelationshipName();
    }

    public function getProfilePage(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getProfilePage();
    }

    public function getTenantProfilePage(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getTenantProfilePage();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getTenantProfileUrl(array $parameters = []): ?string
    {
        $parameters['tenant'] ??= $this->getTenant();

        return $this->getCurrentPanelOrDefault()->getTenantProfileUrl($parameters);
    }

    public function getTenantRegistrationPage(): ?string
    {
        return $this->getCurrentPanelOrDefault()->getTenantRegistrationPage();
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getTenantRegistrationUrl(array $parameters = []): ?string
    {
        return $this->getCurrentPanelOrDefault()->getTenantRegistrationUrl($parameters);
    }

    public function getTheme(): Theme
    {
        return $this->getCurrentPanelOrDefault()->getTheme();
    }

    public function getUserAvatarUrl(Model | Authenticatable $user): string
    {
        if ($user instanceof HasAvatar) {
            $avatar = $user->getFilamentAvatarUrl();
        } else {
            $avatar = $user->getAttributeValue('avatar_url');
        }

        if ($avatar) {
            return $avatar;
        }

        return app($this->getDefaultAvatarProvider())->get($user);
    }

    public function getUserDefaultTenant(HasTenants | Model | Authenticatable $user): ?Model
    {
        $tenant = null;
        $panel = $this->getCurrentPanelOrDefault();

        if ($user instanceof HasDefaultTenant) {
            $tenant = $user->getDefaultTenant($panel);
        }

        if (! $tenant) {
            $tenant = Arr::first($this->getUserTenants($user));
        }

        return $tenant;
    }

    /**
     * @return array<Action>
     */
    public function getUserMenuItems(): array
    {
        return $this->getCurrentPanelOrDefault()->getUserMenuItems();
    }

    public function getUserName(Model | Authenticatable $user): string
    {
        if ($user instanceof HasName) {
            return $user->getFilamentName();
        }

        return $user->getAttributeValue('name');
    }

    /**
     * @return array<Model>
     */
    public function getUserTenants(HasTenants | Model | Authenticatable $user): array
    {
        $tenants = $user->getTenants($this->getCurrentPanelOrDefault());

        if ($tenants instanceof Collection) {
            $tenants = $tenants->all();
        }

        return $tenants;
    }

    public function getUrl(?Model $tenant = null): ?string
    {
        return $this->getCurrentPanelOrDefault()->getUrl($tenant);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function getVerifyEmailUrl(MustVerifyEmail | Model | Authenticatable $user, array $parameters = []): string
    {
        return $this->getCurrentPanelOrDefault()->getVerifyEmailUrl($user, $parameters);
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return $this->getCurrentPanelOrDefault()->getWidgets();
    }

    public function hasBreadcrumbs(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasBreadcrumbs();
    }

    public function hasBroadcasting(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasBroadcasting();
    }

    public function hasCollapsibleNavigationGroups(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasCollapsibleNavigationGroups();
    }

    public function hasDarkMode(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasDarkMode();
    }

    public function hasDarkModeForced(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasDarkModeForced();
    }

    public function hasDatabaseNotifications(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasDatabaseNotifications();
    }

    public function hasLazyLoadedDatabaseNotifications(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasLazyLoadedDatabaseNotifications();
    }

    public function hasEmailVerification(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasEmailVerification();
    }

    public function hasLogin(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasLogin();
    }

    public function hasNavigation(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasNavigation();
    }

    public function hasPasswordReset(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasPasswordReset();
    }

    public function hasPlugin(string $id): bool
    {
        return $this->getCurrentPanelOrDefault()->hasPlugin($id);
    }

    public function hasProfile(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasProfile();
    }

    public function hasRegistration(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasRegistration();
    }

    public function hasTenantMenu(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTenantMenu();
    }

    public function hasTenancy(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTenancy();
    }

    public function hasTenantBilling(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTenantBilling();
    }

    public function hasTenantProfile(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTenantProfile();
    }

    public function hasTenantRegistration(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTenantRegistration();
    }

    public function hasTopbar(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTopbar();
    }

    public function hasTopNavigation(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasTopNavigation();
    }

    public function hasUnsavedChangesAlerts(): bool
    {
        return $this->getCurrentPanelOrDefault()->hasUnsavedChangesAlerts();
    }

    public function isGlobalSearchEnabled(): bool
    {
        if ($this->getGlobalSearchProvider() === null) {
            return false;
        }

        foreach ($this->getResources() as $resource) {
            if ($resource::canGloballySearch()) {
                return true;
            }
        }

        return false;
    }

    public function isServing(): bool
    {
        return $this->isServing;
    }

    public function isSidebarCollapsibleOnDesktop(): bool
    {
        return $this->getCurrentPanelOrDefault()->isSidebarCollapsibleOnDesktop();
    }

    public function isSidebarFullyCollapsibleOnDesktop(): bool
    {
        return $this->getCurrentPanelOrDefault()->isSidebarFullyCollapsibleOnDesktop();
    }

    public function registerPanel(Panel $panel): void
    {
        app(PanelRegistry::class)->register($panel);
    }

    /**
     * @deprecated Use the `\Filament\Support\Facades\FilamentView::renderHook()` method instead.
     */
    public function renderHook(string $name): Htmlable
    {
        return FilamentView::renderHook($name);
    }

    public function serving(Closure $callback): void
    {
        Event::listen(ServingFilament::class, $callback);
    }

    public function currentDomain(?string $domain): void
    {
        $this->currentDomain = $domain;
    }

    public function setCurrentPanel(Panel | string | null $panel): void
    {
        if (is_string($panel)) {
            $panel = $this->getPanel($panel);
        }

        $this->currentPanel = $panel;
    }

    public function setServingStatus(bool $condition = true): void
    {
        $this->isServing = $condition;
    }

    public function setTenant(?Model $tenant, bool $isQuiet = false): void
    {
        $this->tenant = $tenant;

        if ($tenant && (! $isQuiet)) {
            event(new TenantSet($tenant, $this->auth()->user()));
        }
    }

    /**
     * @deprecated Use the `navigationGroups()` method on the panel configuration instead.
     *
     * @param  array<string | int, NavigationGroup | string>  $groups
     */
    public function registerNavigationGroups(array $groups): void
    {
        try {
            $this->getDefaultPanel()->navigationGroups($groups);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `navigationGroups()` method on the panel configuration to register navigation groups. See the documentation - https://filamentphp.com/docs/panels/navigation#customizing-navigation-groups');
        }
    }

    /**
     * @deprecated Use the `navigationItems()` method on the panel configuration instead.
     *
     * @param  array<NavigationItem>  $items
     */
    public function registerNavigationItems(array $items): void
    {
        try {
            $this->getDefaultPanel()->navigationItems($items);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `navigationItems()` method on the panel configuration to register navigation items. See the documentation - https://filamentphp.com/docs/panels/navigation#registering-custom-navigation-items');
        }
    }

    /**
     * @deprecated Use the `pages()` method on the panel configuration instead.
     *
     * @param  array<class-string>  $pages
     */
    public function registerPages(array $pages): void
    {
        try {
            $this->getDefaultPanel()->pages($pages);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `pages()` method on the panel configuration to register pages.');
        }
    }

    /**
     * @deprecated Use the `renderHook()` method on the panel configuration instead.
     */
    public function registerRenderHook(string $name, Closure $hook): void
    {
        FilamentView::registerRenderHook($name, $hook);
    }

    /**
     * @deprecated Use the `resources()` method on the panel configuration instead.
     *
     * @param  array<class-string>  $resources
     */
    public function registerResources(array $resources): void
    {
        try {
            $this->getDefaultPanel()->resources($resources);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `resources()` method on the panel configuration to register resources.');
        }
    }

    /**
     * @deprecated Register scripts using the `FilamentAsset` facade instead.
     *
     * @param  array<mixed>  $scripts
     */
    public function registerScripts(array $scripts, bool $shouldBeLoadedBeforeCoreScripts = false): void
    {
        throw new Exception('Please use the `FilamentAsset` facade to register scripts. See the documentation - https://filamentphp.com/docs/support/assets#registering-javascript-files');
    }

    /**
     * @deprecated Register script data using the `FilamentAsset` facade instead.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerScriptData(array $data): void
    {
        FilamentAsset::registerScriptData($data);
    }

    /**
     * @deprecated Register styles using the `FilamentAsset` facade instead.
     *
     * @param  array<mixed>  $styles
     */
    public function registerStyles(array $styles): void
    {
        throw new Exception('Please use the `FilamentAsset` facade to register styles. See the documentation - https://filamentphp.com/docs/support/assets#registering-css-files');
    }

    /**
     * @deprecated Use the `theme()` method on the panel configuration instead.
     */
    public function registerTheme(string | Htmlable | null $theme): void
    {
        try {
            $this->getDefaultPanel()->theme($theme);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `theme()` method on the panel configuration to register themes.');
        }
    }

    /**
     * @deprecated Use the `viteTheme()` method on the panel configuration instead.
     *
     * @param  string | array<string>  $theme
     */
    public function registerViteTheme(string | array $theme, ?string $buildDirectory = null): void
    {
        try {
            $this->getDefaultPanel()->viteTheme($theme, $buildDirectory);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `viteTheme()` method on the panel configuration to register themes.');
        }
    }

    /**
     * @deprecated Use the `userMenuItems()` method on the panel configuration instead.
     *
     * @param  array<MenuItem>  $items
     */
    public function registerUserMenuItems(array $items): void
    {
        try {
            $this->getDefaultPanel()->userMenuItems($items);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `userMenuItems()` method on the panel configuration to register user menu items. See the documentation - https://filamentphp.com/docs/panels/navigation#customizing-the-user-menu');
        }
    }

    /**
     * @deprecated Use the `widgets()` method on the panel configuration instead.
     *
     * @param  array<class-string>  $widgets
     */
    public function registerWidgets(array $widgets): void
    {
        try {
            $this->getDefaultPanel()->widgets($widgets);
        } catch (NoDefaultPanelSetException $exception) {
            throw new Exception('Please use the `widgets()` method on the panel configuration to register widgets.');
        }
    }

    public function getDefaultThemeMode(): ThemeMode
    {
        return $this->getCurrentPanelOrDefault()->getDefaultThemeMode();
    }

    public function arePasswordsRevealable(): bool
    {
        return $this->getCurrentPanelOrDefault()->arePasswordsRevealable();
    }

    public function getCurrentDomain(?string $testingDomain = null): string
    {
        if (filled($this->currentDomain)) {
            return $this->currentDomain;
        }

        if (app()->runningUnitTests()) {
            return $testingDomain;
        }

        if (app()->runningInConsole()) {
            throw new Exception('The current domain is not set, but multiple domains are registered for the panel. Please use [Filament::currentDomain(\'example.com\')] to set the current domain to ensure that panel URLs are generated correctly.');
        }

        return request()->getHost();
    }

    public function getTenancyScopeName(): string
    {
        return $this->getCurrentPanelOrDefault()->getTenancyScopeName();
    }

    /**
     * @return array<MultiFactorAuthenticationProvider>
     */
    public function getMultiFactorAuthenticationProviders(): array
    {
        return $this->getCurrentPanelOrDefault()->getMultiFactorAuthenticationProviders();
    }

    public function isAuthorizationStrict(): bool
    {
        return $this->getCurrentPanelOrDefault()->isAuthorizationStrict();
    }
}
