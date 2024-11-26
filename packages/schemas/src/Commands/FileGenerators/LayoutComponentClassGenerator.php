<?php

namespace Filament\Schemas\Commands\FileGenerators;

use Filament\Schemas\Components\Component;
use Filament\Support\Commands\FileGenerators\ClassGenerator;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Property;

class LayoutComponentClassGenerator extends ClassGenerator
{
    final public function __construct(
        protected string $fqn,
        protected bool $hasEmbeddedView,
        protected ?string $view,
    ) {}

    public function getNamespace(): string
    {
        return $this->extractNamespace($this->getFqn());
    }

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        return [
            $this->getExtends(),
            ...($this->hasEmbeddedView() ? [HasEmbeddedView::class] : []),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return Component::class;
    }

    /**
     * @return array<class-string>
     */
    public function getImplements(): array
    {
        return [
            ...($this->hasEmbeddedView() ? [HasEmbeddedView::class] : []),
        ];
    }

    protected function addPropertiesToClass(ClassType $class): void
    {
        $this->addViewPropertyToClass($class);
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addMakeMethodToClass($class);
        $this->addToEmbeddedHtmlMethodToClass($class);
    }

    protected function addViewPropertyToClass(ClassType $class): void
    {
        if ($this->hasEmbeddedView()) {
            return;
        }

        $property = $class->addProperty('view', $this->getView())
            ->setProtected()
            ->setType('string');
        $this->configureViewProperty($property);
    }

    protected function configureViewProperty(Property $property): void {}

    protected function addMakeMethodToClass(ClassType $class): void
    {
        $method = $class->addMethod('make')
            ->setPublic()
            ->setStatic()
            ->setReturnType('static')
            ->setBody(<<<'PHP'
                return app(static::class);
                PHP);

        $this->configureMakeMethod($method);
    }

    protected function configureMakeMethod(Method $method): void {}

    protected function addToEmbeddedHtmlMethodToClass(ClassType $class): void
    {
        if (! $this->hasEmbeddedView()) {
            return;
        }

        $method = $class->addMethod('toEmbeddedHtml')
            ->setPublic()
            ->setReturnType('string')
            ->setBody(<<<'PHP'
                ob_start(); ?>

                <div <?= $this->getExtraAttributeBag()->toHtml() ?>>
                    <?= $this->getChildComponentContainer()->toHtml() ?>
                </div>

                <?php return ob_get_clean();
                PHP);

        $this->configureToEmbeddedHtmlMethod($method);
    }

    protected function configureToEmbeddedHtmlMethod(Method $method): void {}

    public function getFqn(): string
    {
        return $this->fqn;
    }

    public function hasEmbeddedView(): bool
    {
        return $this->hasEmbeddedView;
    }

    public function getView(): ?string
    {
        return $this->view;
    }
}
