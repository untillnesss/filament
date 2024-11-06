<?php

namespace Filament\Support\Commands\FileGenerators;

use Filament\Support\Commands\FileGenerators\Contracts\FileGenerator;
use Filament\Support\Config\FileGenerationFlag;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PsrPrinter;

abstract class ClassGenerator implements FileGenerator
{
    protected PhpNamespace $namespace;

    public function getFile(): PhpFile
    {
        $file = (new PhpFile);
        $this->configureFile($file);

        $this->namespace = $file->addNamespace($this->getNamespace());
        $this->useImportsInNamespace($this->namespace, $this->getImports());
        $this->configureNamespace($this->namespace);

        $class = $this->namespace->addClass($this->getBasename());

        if (filled($extends = $this->getExtends())) {
            $class->setExtends($extends);
        }

        $this->addPropertiesToClass($class);
        $this->addMethodsToClass($class);
        $this->configureClass($class);

        return $file;
    }

    protected function configureFile(PhpFile $file): void {}

    protected function configureNamespace(PhpNamespace $namespace): void {}

    protected function configureClass(ClassType $class): void {}

    protected function addPropertiesToClass(ClassType $class): void {}

    protected function addMethodsToClass(ClassType $class): void {}

    /**
     * @return ?class-string
     */
    public function getExtends(): ?string
    {
        return null;
    }

    /**
     * @param  class-string  $name
     */
    public function simplifyFqn(string $name): string
    {
        return $this->namespace->simplifyName($name);
    }

    abstract public function getBasename(): string;

    abstract public function getNamespace(): string;

    protected function extractNamespace(string $fqn): string
    {
        return (string) str($fqn)->beforeLast('\\');
    }

    public function hasEmbeddedPanelResourceSchemas(): bool
    {
        return in_array(FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_SCHEMAS, $this->getFileGenerationFlags());
    }

    public function hasEmbeddedPanelResourceTables(): bool
    {
        return in_array(FileGenerationFlag::EMBEDDED_PANEL_RESOURCE_TABLES, $this->getFileGenerationFlags());
    }

    public function hasPartialImports(): bool
    {
        return in_array(FileGenerationFlag::PARTIAL_IMPORTS, $this->getFileGenerationFlags());
    }

    public function hasPanelResourceClassesOutsideDirectories(): bool
    {
        return in_array(FileGenerationFlag::PANEL_RESOURCE_CLASSES_OUTSIDE_DIRECTORIES, $this->getFileGenerationFlags());
    }

    /**
     * @return array<string>
     */
    public function getFileGenerationFlags(): array
    {
        return config('filament.file_generation.flags') ?? [];
    }

    /**
     * @param  array<string>  $imports
     */
    protected function useImportsInNamespace(PhpNamespace $namespace, array $imports): void
    {
        foreach ($imports as $key => $import) {
            if (is_string($key)) {
                $namespace->addUse($key, alias: $import);

                continue;
            }

            $namespace->addUse($import);
        }
    }

    protected function importUnlessPartial(string $class): void
    {
        if ($this->hasPartialImports()) {
            return;
        }

        $this->namespace->addUse($class);
    }

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        return [];
    }

    public function getPrinter(): Printer
    {
        $printer = new PsrPrinter;
        $printer->linesBetweenProperties = 1;

        return $printer;
    }

    protected function configurePrinter(Printer $printer): void {}

    public function generate(): string
    {
        return $this->getPrinter()->printFile($this->getFile());
    }
}
