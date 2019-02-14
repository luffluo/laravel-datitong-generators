# Laravel Datitong Generators

> This package will generate some files for Datitong.

# Installation
Install by composer
```
$ composer require luffluo/laravel-datitong-generators
```

# Example

## Basic
> It can be generating a controller and templates
```
$ php artisan admin:make ModuleName/ControllerName
```

## With a Model
> It can be generating with a model
```
$ php artisan admin:make ModuleName/ControllerName --model|-m
```

> It can be generating with a model for custom model name
```
$ php artisan admin:make ModuleName/ControllerName -m=ModelName

$ php artisan admin:make ModuleName/ControllerName -m=ParentDir/ModelName
```

## With a Service
> It can be generating with a service
```
$ php artisan admin:make ModuleName/ControllerName --service|-s
```

> It can be generating with a service for custom service name
```
$ php artisan admin:make ModuleName/ControllerName -s=ServiceName

$ php artisan admin:make ModuleName/ControllerName -s=ParentDir/ServiceName
```

## With a Request
> It can be generating with a request
```
$ php artisan admin:make ModuleName/ControllerName --request|-r
```

> It can be generating with a request for custom request name
```
$ php artisan admin:make ModuleName/ControllerName -s=RequestName

$ php artisan admin:make ModuleName/ControllerName -s=ParentDir/RequestName
```