<?php

declare(strict_types=1);

namespace App\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\Loaders\RobotLoader;
use Nette\Utils\Finder;
use Symfony\Component\Console\Command\Command;


class AppExtension extends CompilerExtension {

    private $srcDir;


    public function __construct(string $srcDir) {
        $this->srcDir = $srcDir;
    }


    public function beforeCompile() : void {
        $builder = $this->getContainerBuilder();

        foreach ($this->findFactories() as $factory) {
            if (!$builder->getByType($factory)) {
                $builder->addDefinition($this->allocateName($builder, $factory))
                    ->setImplement($factory);
            }
        }

        foreach ($this->findCommands() as $command) {
            if (!$builder->getByType($command)) {
                $builder->addDefinition($this->allocateName($builder, $command))
                    ->setType($command)
                    ->setAutowired(false)
                    ->addTag('kdyby.console.command');
            }
        }

        foreach ($this->findManagers() as $manager) {
            if (!$builder->getByType($manager)) {
                $builder->addDefinition($this->allocateName($builder, $manager))
                    ->setType($manager);
            }
        }
    }

    private function allocateName(ContainerBuilder $builder, string $class) : string {
        return count($builder->getDefinitions()) . '.' . preg_replace('#\W+#', '_', $class);
    }

    private function findFactories() : array {
        $loader = $this->createNamespaceLoader('Components');
        $loader->rebuild();
        $interfaces = [];

        foreach ($loader->getIndexedClasses() as $class => $file) {
            $reflection = new \ReflectionClass($class);

            if ($reflection->isInterface() && count($reflection->getMethods()) === 1 && $reflection->hasMethod('create')) {
                $interfaces[] = $class;
            }
        }

        return $interfaces;
    }

    private function findCommands() : array {
        $loader = $this->createNamespaceLoader('Commands');
        $loader->rebuild();
        $commands = [];

        foreach ($loader->getIndexedClasses() as $class => $file) {
            $reflection = new \ReflectionClass($class);

            if ($reflection->isSubclassOf(Command::class) && !$reflection->isAbstract()) {
                $commands[] = $class;
            }
        }

        return $commands;
    }

    private function findManagers() : array {
        $loader = new RobotLoader();
        $loader->addDirectory($this->srcDir . '/ORM');
        $loader->acceptFiles = '*Manager.php';
        $loader->rebuild();
        $managers = [];

        foreach ($loader->getIndexedClasses() as $class => $file) {
            $reflection = new \ReflectionClass($class);

            if (!$reflection->isAbstract() && !$reflection->isInterface() && !$reflection->isTrait()) {
                $managers[] = $class;
            }
        }

        return $managers;
    }

    private function createNamespaceLoader(string $namespace) : RobotLoader {
        /** @var \RecursiveDirectoryIterator[] $modules */
        $modules = Finder::findDirectories('/*Module/' . $namespace)->from($this->srcDir);
        $loader = new RobotLoader();

        foreach ($modules as $module) {
            $loader->addDirectory($module->getPathname());
        }

        return $loader;
    }
}
