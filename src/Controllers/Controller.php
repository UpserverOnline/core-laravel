<?php

namespace UpserverOnline\Core\Controllers;

abstract class Controller
{
    /**
     * Wraps the given data and returns a JSON response.
     *
     * @param  array  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function data(array $data)
    {
        return response()->json([
            'data' => $data,
        ]);
    }
}
