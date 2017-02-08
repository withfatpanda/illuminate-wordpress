<?php

namespace FatPanda\Illuminate\WordPress\Console\Commands;

use FatPanda\Illuminate\WordPress\Console\Commands\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CustomPostTypeMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:cpt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Custom Post Type model';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Custom post type';

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $type = basename(str_replace('\\', '/', strtolower($name)));
        if ($this->option('type')) {
            $type = $this->option('type');
        }

        return str_replace('dummy_type', $type, $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return realpath(__DIR__.'/../../../../resources/stubs/cpt.stub');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Models';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the custom post type.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['type', null, InputOption::VALUE_OPTIONAL, 'The unique key to indentify this type'],
        ];
    }
}
