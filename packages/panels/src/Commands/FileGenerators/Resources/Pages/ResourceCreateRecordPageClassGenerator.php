<?php

namespace Filament\Commands\FileGenerators\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Support\Commands\FileGenerators\ClassGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Property;

class ResourceCreateRecordPageClassGenerator extends ClassGenerator
{
    /**
     * @param  class-string<resource>  $resourceFqn
     */
    final public function __construct(
        protected string $fqn,
        protected string $resourceFqn,
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
        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);

        return [
            $this->getResourceFqn(),
            ...(($extendsBasename === class_basename($this->getFqn())) ? [$extends => "Base{$extendsBasename}"] : [$extends]),
        ];
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    public function getExtends(): string
    {
        return CreateRecord::class;
    }

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

    public function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * @return class-string<resource>
     */
    public function getResourceFqn(): string
    {
        return $this->resourceFqn;
    }
}
