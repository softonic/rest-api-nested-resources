<?php

namespace Softonic\RestApiNestedResources\Http\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use ReflectionClass;
use ReflectionMethod;
use Softonic\RestApiNestedResources\Models\MultiKeyModel;

trait SplitPutPatchVerbs
{
    use PathParameters;

    public function update(Request $request)
    {
        $parameters = $this->getPathParameters($request);

        if ($request->isMethod('PATCH')) {
            try {
                $parameters = $this->bindModelParameters($parameters);
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException(
                    "{$e->getModel()} resource not found for " . json_encode($parameters, JSON_THROW_ON_ERROR)
                );
            }

            return App::call([$this, 'modify'], $parameters);
        }

        return App::call([$this, 'replace'], $parameters);
    }

    private function bindModelParameters(array $parameters): array
    {
        $methodArguments = (new ReflectionMethod(__CLASS__, 'modify'))->getParameters();

        $modelMethodArguments = array_filter(
            $methodArguments,
            function ($argument): bool {
                if ($argument->getType() && !$argument->getType()->isBuiltin()) {
                    $class = new ReflectionClass($argument->getType()->getName());

                    return $class->isSubclassOf(Model::class);
                }

                return false;
            }
        );

        if (!empty($modelMethodArguments[0])) {
            $modelMethodArgument = $modelMethodArguments[0];

            $className = $modelMethodArgument->getType()->getName();

            $instance = \App::make($className);

            $id = ($instance instanceof MultiKeyModel) ? $instance::generateIdForField(
                $instance->getKeyName(),
                $parameters
            ) : $parameters[$instance->getKeyName()];

            $modelArgument = $instance::findOrFail($id);

            $parameters = [$className => $modelArgument];
        }

        return $parameters;
    }
}
