<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Softonic\RestApiNestedResources\Http\Traits\PathParameters;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Middleware that checks if model does not exist.
 */
class EnsureModelDoesNotExist
{
    use PathParameters;

    public function handle(Request $request, callable $next, string $modelClass, ...$fieldsToCheck)
    {
        $parametersToCheck = $this->getParametersToCheck($request, $fieldsToCheck);

        $found = (bool)($modelClass)::where($parametersToCheck)
            ->count();

        if ($found) {
            throw new ConflictHttpException(
                "{$modelClass} resource already exists for " . json_encode($parametersToCheck, JSON_THROW_ON_ERROR)
            );
        }

        return $next($request);
    }

    private function getParametersToCheck(Request $request, array $fieldsToCheck): array
    {
        $pathParameters = $this->getPathParameters($request);

        $parameters = array_merge($request->all(), $pathParameters);

        return Arr::only($parameters, $fieldsToCheck);
    }
}
