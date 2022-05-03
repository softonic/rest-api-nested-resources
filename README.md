REST API nested resources
====================

[![Latest Version](https://img.shields.io/github/release/softonic/rest-api-nested-resources.svg?style=flat-square)](https://github.com/softonic/rest-api-nested-resources/releases)
[![Software License](https://img.shields.io/badge/license-Apache%202.0-blue.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://github.com/softonic/rest-api-nested-resources/actions/workflows/build.yml/badge.svg)](https://github.com/softonic/rest-api-nested-resources/actions/workflows/build.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/softonic/rest-api-nested-resources.svg?style=flat-square)](https://packagist.org/packages/softonic/rest-api-nested-resources)
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/softonic/rest-api-nested-resources.svg?style=flat-square)](http://isitmaintained.com/project/softonic/rest-api-nested-resources "Average time to resolve an issue")
[![Percentage of issues still open](http://isitmaintained.com/badge/open/softonic/rest-api-nested-resources.svg?style=flat-square)](http://isitmaintained.com/project/softonic/rest-api-nested-resources "Percentage of issues still open")

Utilities to work with REST APIs with nested resources

Main features
-------------

* MultiKeyModel: allows to have nested resources with composite primary keys
* EnsureModelExists: middleware to validate that a resource exists (used to ensure that a parent resource exists)
* EnsureModelDoesNotExist: middleware to validate that the resource we want to create doesn't already exist
* SubstituteBindings: a personalization of the Laravel's SubstituteBindings middleware to work with nested resources
* SplitPutPatchVerbs: trait that allows the controller to split the "update" method into "modify" (PATCH) and "replace" (PUT) CRUDL methods

Installation
-------------

You can require the last version of the package using composer
```bash
composer require softonic/rest-api-nested-resources
```

### Configuration

* MultiKeyModel
```php
class UserCommentModel extends MultiKeyModel
{
    /**
     * Identifiers to be hashed and used in the real primary and foreign keys.
     */
    protected static array $generatedIds = [
        'id_user_comment' => [
            'id_user',
            'id_comment',
        ],
    ];
}
```

* EnsureModelExists and EnsureModelDoesNotExist
```php
class UserCommentController extends Controller
{
    protected function setMiddlewares(Request $request)
    {
        $this->middleware(
            'App\Http\Middleware\EnsureModelExists:App\Models\User,id_user',
            ['only' => ['store', 'update']]
        );

        $this->middleware(
            'App\Http\Middleware\EnsureModelDoesNotExist:App\Models\UserComment,id_user,id_comment',
            ['only' => 'store']
        );
    }
}
```

* SubstituteBindings
```php
use App\Models\UserComment;

class UserCommentController extends Controller
{
    public function show(UserComment $userComment)
    {
        ...
    }
}
```

* SplitPutPatchVerbs
```php
use App\Models\UserComment;

class UserCommentController extends Controller
{
    use SplitPutPatchVerbs;

    public function modify(UserComment $userComment, Request $request)
    {
        ...
    }

    public function replace(Request $request, string $id_user, string $id_comment)
    {
        ...
    }
}
```

Testing
-------

`softonic/rest-api-nested-resources` has a [PHPUnit](https://phpunit.de) test suite, and a coding style compliance test suite using [PHP CS Fixer](http://cs.sensiolabs.org/).

To run the tests, run the following command from the project folder.

``` bash
$ make tests
```

To open a terminal in the dev environment:
``` bash
$ make debug
```

License
-------

The Apache 2.0 license. Please see [LICENSE](LICENSE) for more information.
