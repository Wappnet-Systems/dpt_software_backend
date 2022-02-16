<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($data = [], $message = null, $code = 200)
    {
    	$response = [
            'code'    => $code,
            'type'    => 'success',
            'message' => $message,
            'data'    => $data,
        ];

        return response()->json($response, $code);
    }

    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($message = null, $data = [], $code = 404)
    {
    	$response = [
            'code'    => $code,
            'type'    => 'failure',
            'message' => $message,
            'data'    => $data
        ];

        return response()->json($response, $code);
    }
}
