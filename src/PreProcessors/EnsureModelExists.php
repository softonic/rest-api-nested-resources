<?php

namespace Softonic\RestApiNestedResources\PreProcessors;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnsureModelExists
{
    public function process(string $modelClass, array $fieldsToCheck, array $parameters): void
    {
        $parametersToCheck = [];
        foreach ($fieldsToCheck as $fieldToCheck) {
            $fieldToCheckParts = explode('=', (string) $fieldToCheck);

            $field = end($fieldToCheckParts);
            if (!isset($parameters[$field])) {
                return;
            }

            $parametersToCheck[$fieldToCheckParts[0]] = $parameters[$field];
        }

        $found = (bool)($modelClass)::where($parametersToCheck)
                                    ->count();

        if (!$found) {
            throw new ConflictHttpException(
                "{$modelClass} resource does not exist for " . json_encode($parametersToCheck, JSON_THROW_ON_ERROR)
            );
        }
    }
}
