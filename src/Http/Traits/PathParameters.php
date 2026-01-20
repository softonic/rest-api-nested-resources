<?php

namespace Softonic\RestApiNestedResources\Http\Traits;

use Illuminate\Http\Request;

trait PathParameters
{
    public function getPathParameters(Request $request): array
    {
        $pathParameters = $request->route()
            ->parameters();

        $pathParametersKeys = array_map(
            fn (int|string $key) => 'id_' . $key,
            array_keys($pathParameters)
        );

        return array_combine($pathParametersKeys, $pathParameters);
    }
}
