<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Illuminate\Http\Request;
use Softonic\RestApiNestedResources\Http\Traits\PathParameters;
use Softonic\RestApiNestedResources\PreProcessors\EnsureModelExists as EnsureModelExistsProcessor;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Middleware that checks if model exists.
 */
class EnsureModelExists
{
    use PathParameters;

    public function __construct(private EnsureModelExistsProcessor $ensureModelExists)
    {
    }

    /**
     * @throws ConflictHttpException
     */
    public function handle(Request $request, callable $next, string $modelClass, ...$fieldsToCheck)
    {
        $pathParameters = $this->getPathParameters($request);

        $parameters = array_merge($request->all(), $pathParameters);

        $this->ensureModelExists->process($modelClass, $fieldsToCheck, $parameters);

        return $next($request);
    }
}
