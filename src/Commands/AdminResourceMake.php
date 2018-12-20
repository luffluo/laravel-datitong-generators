<?php

namespace Luffluo\Generators\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class AdminResourceMake extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:make';

    protected $types = [
        'controller' => [],
        'service'    => [],
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建后台资源';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Resource';

    protected $replacements = [];

    protected $moduleName;

    protected $controllerName;

    protected $modelName;

    protected $serviceName;

    protected $requestName;

    protected function parseInputName($name)
    {
        $name = ltrim($name, '\\/');

        return str_replace('/', '\\', $name);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $inputName = $this->getNameInput();
        $inputName = $this->parseInputName($inputName);
        list($this->moduleName, $this->controllerName) = explode('\\', $inputName);
        $this->serviceName = $this->controllerName;

        $model = $this->option('model');
        if ('no-model' !== $model) {
            $this->types['model'] = [];

            $this->modelName = $model;
            if (is_null($model)) {
                $this->modelName = $this->controllerName;
            }
        }

        $this->initReplacements();

        foreach ($this->types as $type => $file) {

            $qualifyMethod = 'qualify' . ucfirst($type) . 'Class';
            $getNameMethod = 'get' . ucfirst($type) . 'Name';

            $name = $this->$qualifyMethod($this->$getNameMethod());

            $files[$type] = [
                'name' => $name,
                'path' => $this->getPath($name),
            ];
        }

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        // if ((! $this->hasOption('force') ||
        //         ! $this->option('force')) &&
        //     $this->alreadyExists($this->getNameInput())) {
        //     $this->error($this->type.' already exists!');
        //
        //     return false;
        // }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        foreach ($files as $type => $file) {
            $this->makeDirectory($file['path']);
            $this->files->put($file['path'], $this->buildClass($file['name'], $type));
            $this->info(ucfirst($type) . ' created successfully.');
        }

        $views = [
            'index',
            'add',
            'edit',
            '_form'
        ];
        foreach ($views as $view) {

            $path = $this->getViewPath($this->getViewModuleName() . '/' . $this->getViewControllerName() . '/' . $view);
            $this->makeDirectory($path);
            $this->files->put($path, $this->buildView($view));
            $this->info($view . ' view created successfully.');
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $type = func_get_arg(0);

        $stub = '';
        switch ($type) {
            case 'controller':
                $stub = 'controller.stub';
                break;

            case 'request':
                $stub = 'request.stub';
                break;

            case 'service':
                $stub = 'service.stub';
                break;

            case 'model':
                $stub = 'model.stub';
                break;
        }

        return __DIR__ . '/../stubs/admin/' . $stub;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getViewStub($name)
    {
        $stub = $name . '.stub';

        return __DIR__ . '/../stubs/admin/views/' . $stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultControllerNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Controllers\Admin';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultModelNamespace($rootNamespace)
    {
        return $rootNamespace . '\Models';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultServiceNamespace($rootNamespace)
    {
        return $rootNamespace . '\Services';
    }

    protected function getDefaultRequestNamespace($rootNamespace)
    {
        return $rootNamespace . '\Http\Requests\Admin';
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    protected function getViewPath($name)
    {
        return $this->laravel['path.resources'] . '/views/admin/' . str_replace('\\', '/', $name) . '.blade.php';
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function qualifyControllerClass($name)
    {
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        $name = $this->moduleName . '\\' . $name;

        return $this->qualifyControllerClass(
            $this->getDefaultControllerNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function qualifyRequestClass($name)
    {
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyRequestClass(
            $this->getDefaultRequestNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function qualifyServiceClass($name)
    {
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {

            return $name;
        }

        return $this->qualifyServiceClass(
            $this->getDefaultServiceNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Parse the class name and format according to the root namespace.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function qualifyModelClass($name)
    {
        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->qualifyModelClass(
            $this->getDefaultModelNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $type = func_get_arg(1);

        $stub = $this->files->get($this->getStub($type));

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    protected function buildView($name)
    {
        $stub = $this->files->get($this->getViewStub($name));

        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     *
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        parent::replaceNamespace($stub, $name);

        $stub = str_replace(
            array_keys($this->replacements),
            array_values($this->replacements),
            $stub
        );

        return $this;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string $rawName
     *
     * @return bool
     */
    protected function alreadyExists($path)
    {
        return $this->files->exists($path);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', null, InputOption::VALUE_OPTIONAL, 'Generate with a model.', 'no-model'],
        ];
    }

    public function getControllerName()
    {
        return str_finish($this->controllerName, 'Controller');
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function getModelTableName()
    {
        return str_plural(snake_case($this->modelName));
    }

    public function getServiceName()
    {
        return str_finish($this->serviceName, 'Service');
    }

    public function getRequestName()
    {
        return str_finish($this->requestName, 'Request');
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getViewModuleName()
    {
        return snake_case($this->moduleName);
    }

    public function getViewControllerName()
    {
        return snake_case($this->controllerName);
    }

    protected function initReplacements()
    {
        $this->replacements['dummy_module'] = $this->getViewModuleName();
        $this->replacements['dummy_class'] = $this->getViewControllerName();

        $this->replacements['DummyModelNamespace'] = $this->qualifyModelClass($this->getModelName());
        $this->replacements['DummyModelClass'] = $this->getModelName();
        $this->replacements['dummy_table'] = $this->getModelTableName();

        $this->replacements['DummyServiceNamespace'] = $this->qualifyServiceClass($this->getServiceName());
        $this->replacements['DummyServiceClass'] = $this->getServiceName();
    }
}
