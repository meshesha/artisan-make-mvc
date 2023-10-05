# Aartisan make Mvc

Laravel package that adds artisan command to create view, controller and route using existing Model. One command line and you can already see results.

## Requirements

- laravel >= 5.6

## Installation

### Downloading

Via [composer](http://getcomposer.org):

```bash
composer require meshesha/artisan-make-mvc
```

### Publish config file

```bash
php artisan vendor:publish --provider="Meshesha\ArtisanMakeMvc\ArtisanMakeMvcServiceProvider"

```

## Usage
- This package depends on an existing model.
So it is required to create a model: 

```bash
php artisan make:model Post -m
```
- complete the required column names in the migration file,
and migrate to the database.

- Inside the model file you must add $fillable

```php
protected $fillable = [
'title',
'body',
];
```



### Command options

```

php artisan make:mvc {model} {--W|incviews=true} {--F|viewfolder=} {--H|includehidden=true} {--C|inccontroller=true} {--R|incroute=true}

model :  the existing model name (required) (e.g. 'Post')
--incviews      | -W : Whether to include\create blade views  (optional) (default value : true).
--viewfolder    | -F : sub folder inside "resources\views" (optional) (default value : db table name of model (e.g. : 'posts'))
--includehidden | -H : Whether to include hidden fields that are mentioned in the model via 'protected $hidden' (optional) (default value : false)
--inccontroller | -C : Whether to include\create controller (optional) (default value : true)
--incroute      | -R : Whether or not to add a route to the routers file (optional) (default value : true)

--factory            : Add factory file (ModelNameFactory.php)  in "database/Factories" directory.
--test               : Add test file (ModelNameTest.php) in "tests/Feature" directory and factory file  in "database/Factories" directory (because it is required for testing).

```

### Config file (config\ArtisanMakeMvc.php)

```php
return [
    "template" => "default", //templates: default (Vanilla CSS), bootstrap, tailwind
    "extends" => "", //e.g: @extends('layouts.app')
    "section" => "", //e.g: @section('content')
    "endsection" => "", //e.g: @endsection
];

```

#### Example:

```bash

php artisan make:mvc Post

# 'Post' - model name
```

This command will create:

- [PostController.php](https://github.com/meshesha/artisan-make-mvc/wiki/PostController)
- [views/posts/create.blade.php](<https://github.com/meshesha/artisan-make-mvc/wiki/create.blade.php(default-tmpl)>)
- [views/posts/edit.blade.php](<https://github.com/meshesha/artisan-make-mvc/wiki/edit.blade.php(default)>)
- [views/posts/index.blade.php](<https://github.com/meshesha/artisan-make-mvc/wiki/index.blade.php(default)>)
- [views/posts/show.blade.php](<https://github.com/meshesha/artisan-make-mvc/wiki/show.blade.php(default)>)

And add to routes/web.php:
For laravel version >= 8.0.0

```php
// posts:
Route::resource('posts', App\Http\Controllers\PostController::class);
```

For laravel version < 8.0.0

```php
// posts:
Route::resource('posts', 'PostController');
```

### undo

#### undo last action

```bash

php artisan mvc:undo

```

Will delete all recently created files.

#### more options:

```
-L | --list    : shows history list
-S | --select  : select from list for 'undo' action.

```

## License

MIT License (MIT). Please see the
[license file](LICENSE.md) for more information.
