<?php

namespace Chuangke\LaravelGenerators\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class AdminMake extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'admin:make';

    /**
     * 要创建的类文件
     *
     * @var array
     */
    protected $classes = [
        'controller' => [],
    ];

    /**
     * 要创建的模板文件
     *
     * @var array
     */
    protected $templates = [
        'index',
        'add',
        'edit',
        '_form',
    ];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create module, request, controller, service, model and four templates (index, add, edit and _form)';

    protected $replacements = [];

    protected $moduleName;

    protected $controllerName;

    protected $modelName;
    protected $modelNamespace;

    protected $serviceName;
    protected $serviceNamespace;

    protected $requestName;
    protected $requestNamespace;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->handleNameInput($this->getNameInput());
        $this->handleModel($this->controllerName);
        $this->handleRequest($this->controllerName);
        $this->handleService($this->controllerName);

        $this->initReplacements();

        foreach ($this->classes as $type => $file) {

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
        if ($this->alreadyExists(collect($files)->first()['path'])) {

            $this->error('These files already exists!');

            return false;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        foreach ($files as $type => $file) {
            $this->makeDirectory($file['path']);
            $this->files->put($file['path'], $this->buildClass($file['name'], $type));
            $this->info(ucfirst($type) . ' created successfully.');
        }

        foreach ($this->templates as $view) {

            $path = $this->getViewPath($this->getViewModuleName() . '/' . $this->getViewControllerName() . '/' . $view);
            $this->makeDirectory($path);
            $this->files->put($path, $this->buildView($view));
            $this->info($view . ' view created successfully.');
        }
    }

    protected function parseClassName($name)
    {
        $name = ltrim($name, '\\/');

        return str_replace('/', '\\', $name);
    }

    public function handleModel($default)
    {
        $default = str_plural($default, 1);

        // 处理输入的 model
        if ('nothing' !== $this->option('model')) {
            $this->classes['model'] = [];

            if (! is_null($model = $this->option('model'))) {
                $default = $model;
            }
        }

        $this->setModelName($default);
    }

    public function handleService($default)
    {
        // 处理输入的 service
        if ('nothing' !== $this->option('service')) {
            $this->classes['service'] = [];

            if (! is_null($service = $this->option('service'))) {
                $default = $service;
            }
        }

        $this->setServiceName($default);
    }

    public function handleRequest($default)
    {
        if ('nothing' !== $this->option('request')) {
            $this->classes['request'] = [];

            if (! is_null($request = $this->option('request'))) {
                $default = $request;
            }
        }

        $this->setRequestName($default);
    }

    public function handleNameInput($inputName)
    {
        $inputName = $this->parseClassName($inputName);
        list($this->moduleName, $this->controllerName) = explode('\\', $inputName);
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
        $name = str_replace_first($this->rootNamespace(), '', $name);

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

        if (starts_with($name, $rootNamespace)) {
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

        if (starts_with($name, $rootNamespace)) {
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

        if (starts_with($name, $rootNamespace)) {

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

        if (starts_with($name, $rootNamespace)) {
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

    public function getControllerName()
    {
        return str_finish($this->controllerName, 'Controller');
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function setModelName($name)
    {
        $name = $this->parseClassName($name);

        $this->modelName = array_last(explode('\\', $name));

        $this->setModelNamespace($name);

        return $this;
    }

    public function getModelNamespace()
    {
        return $this->modelNamespace;
    }

    public function setModelNamespace($name)
    {
        $name = $this->parseClassName($name);

        $this->modelNamespace = str_replace_last('\\' . $this->getModelName(), '', $this->qualifyModelClass($name));

        return $this;
    }

    public function getModelTableName()
    {
        return str_plural(snake_case($this->modelName));
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }

    public function setServiceName($name)
    {
        $name = $this->parseClassName($name);
        $name = str_finish($name, 'Service');

        $this->serviceName = array_last(explode('\\', $name));

        $this->setServiceNamespace($name);

        return $this;
    }

    public function getServiceNamespace()
    {
        return $this->serviceNamespace;
    }

    public function setServiceNamespace($name)
    {
        $name = $this->parseClassName($name);

        $this->serviceNamespace = str_replace_last('\\' . $this->getServiceName(), '', $this->qualifyServiceClass($name));

        return $this;
    }

    public function getRequestName()
    {
        return $this->requestName;
    }

    public function setRequestName($name)
    {
        $name = $this->parseClassName($name);
        $name = str_finish($name, 'Request');

        $this->requestName = array_last(explode('\\', $name));

        $this->setRequestNamespace($name);

        return $this;
    }

    public function getRequestNamespace()
    {
        return $this->requestNamespace;
    }

    public function setRequestNamespace($name)
    {
        $name = $this->parseClassName($name);

        $this->requestNamespace = str_replace_last('\\' . $this->getRequestName(), '', $this->qualifyRequestClass($name));

        return $this;
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

        $this->replacements['DummyRequestUse'] = str_finish($this->getRequestNamespace(), '\\' . $this->getRequestName());
        $this->replacements['DummyRequestNamespace'] = $this->getRequestNamespace();
        $this->replacements['DummyRequestClass'] = $this->getRequestName();

        $this->replacements['DummyControllerNamespace'] = str_replace('\\' . $this->getControllerName(), '', $this->qualifyControllerClass($this->getControllerName()));
        $this->replacements['DummyControllerClass'] = $this->getControllerName();

        $this->replacements['DummyModelUse'] = str_finish($this->getModelNamespace(), '\\' . $this->getModelName());
        $this->replacements['DummyModelNamespace'] = $this->getModelNamespace();
        $this->replacements['DummyModelClass'] = $this->getModelName();
        $this->replacements['dummy_table'] = $this->getModelTableName();

        $this->replacements['DummyServiceUse'] = str_finish($this->getServiceNamespace(), '\\' . $this->getServiceName());
        $this->replacements['DummyServiceNamespace'] = $this->getServiceNamespace();
        $this->replacements['DummyServiceClass'] = $this->getServiceName();
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Generate with a model.', 'nothing'],
            ['service', 's', InputOption::VALUE_OPTIONAL, 'Generate with a service.', 'nothing'],
            ['request', 'r', InputOption::VALUE_OPTIONAL, 'Generate with a request.', 'nothing'],
        ];
    }
}
