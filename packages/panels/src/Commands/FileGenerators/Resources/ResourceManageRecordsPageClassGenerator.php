<?php

namespace Filament\Commands\FileGenerators\Resources;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Resources\Resource;
use Filament\Support\Commands\FileGenerators\ClassGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Property;

class ResourceManageRecordsPageClassGenerator extends ClassGenerator
{
    /**
     * @param  class-string<resource>  $resourceFqn
     */
    final public function __construct(
        protected string $fqn,
        protected string $resourceFqn,
    ) {}

    protected function addPropertiesToClass(ClassType $class): void
    {
        $this->addResourcePropertyToClass($class);
    }

    protected function addResourcePropertyToClass(ClassType $class): void
    {
        $property = $class->addProperty('resource', new Literal("{$this->simplifyFqn($this->getResourceFqn())}::class"))
            ->setProtected()
            ->setStatic()
            ->setType('string');
        $this->configureResourceProperty($property);
    }

    protected function configureResourceProperty(Property $property): void {}

    protected function addMethodsToClass(ClassType $class): void
    {
        $this->addGetHeaderActionsMethodToClass($class);
    }

    protected function addGetHeaderActionsMethodToClass(ClassType $class): void
    {
        $actions = array_map(
            fn (string $action): string => (string) new Literal("{$this->simplifyFqn($action)}::make(),"),
            $this->getHeaderActions(),
        );

        $actionsOutput = implode(PHP_EOL . '    ', $actions);

        $method = $class->addMethod('getHeaderActions')
            ->setProtected()
            ->setReturnType('array')
            ->setBody(
                <<<PHP
                return [
                    {$actionsOutput}
                ];
                PHP
            );

        $this->configureGetHeaderActionsMethod($method);
    }

    /**
     * @return array<class-string<Action>>
     */
    public function getHeaderActions(): array
    {
        return [
            CreateAction::class,
        ];
    }

    protected function configureGetHeaderActionsMethod(Method $method): void {}

    public function getExtends(): string
    {
        return ManageRecords::class;
    }

    public function getFqn(): string
    {
        return $this->fqn;
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getNamespace(): string
    {
        return $this->extractNamespace($this->getFqn());
    }

    /**
     * @return class-string<resource>
     */
    public function getResourceFqn(): string
    {
        return $this->resourceFqn;
    }

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);

        return [
            $this->getResourceFqn(),
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
            ...($this->hasPartialImports() ? [
                'Filament\Actions',
            ] : $this->getHeaderActions()),
        ];
    }
}
