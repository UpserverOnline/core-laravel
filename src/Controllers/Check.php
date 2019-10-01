<?php

namespace UpserverOnline\Core\Controllers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use UpserverOnline\Core\ApiResponse;
use UpserverOnline\Core\Upserver;

class Check extends Controller
{
    use ValidatesRequests;

    /**
     * Fetches the check from the Upserver Api, builds and runs the monitor
     * and responds with the result and errors and warnings.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        // Validate the posted data
        $data = $this->validate($request, [
            'token' => 'required',
        ]);

        // Get the check from the Api
        $checkData = tap(Upserver::check($data['token']), function (ApiResponse $response) {
            abort_unless($response->isSuccessful(), $response->statusCode());
        })->get('data');

        // Find the monitor class
        $monitorClass = app('upserver-online.monitors')[$checkData['monitor']];

        // Prepare the constructor parameters for the monitor
        $parameters = collect($checkData['options'])->keyBy(function ($value, $parameter) {
            return Str::camel($parameter);
        })->all();

        // Make the monitor
        $monitor = app()->makeWith($monitorClass, $parameters)->setCheckToken($data['token']);

        // Run the monitor and return the results
        return $this->data([
            'token'        => $checkData['token'],
            'passed'       => $passed = $monitor->passes(),
            'failed'       => !$passed,
            'has_errors'   => $monitor->hasError(),
            'errors'       => $monitor->errors(),
            'has_warnings' => $monitor->hasWarning(),
            'warnings'     => $monitor->warnings(),
        ]);
    }
}
