# Laravel Datitong Generators

> This package will generate some files for Datitong.

# Installation
Install by composer
```
$ composer require luffluo/laravel-datitong-generators
```

# Example
> It can be generating a controller and templates
```
$ php artisan admin:make ModuleName/ControllerName
```

> It can be generating with a model
```
$ php artisan admin:make ModuleName/ControllerName --model|-m
```

> It can be generating with a model for custom model name
```
$ php artisan admin:make ModuleName/ControllerName -m=ModelName

$ php artisan admin:make ModuleName/ControllerName -m=ParentDir/ModelName
```

> It can be generating with a service
```
$ php artisan admin:make ModuleName/ControllerName --service|-s
```

> It can be generating with a service for custom service name
```
$ php artisan admin:make ModuleName/ControllerName -s=ServiceName

$ php artisan admin:make ModuleName/ControllerName -s=ParentDir/ServiceName
```

# Other
You will like it.