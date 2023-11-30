<?php

/**
 * Create success Response
 * @param string $message
 * @param array $data
 * @param integer $code
 * @param boolean $success
 * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
 */
function create_response($message = null, $data = null, $code = 200, $success = true)
{
    $res = ['success' => $success];

    if (!empty($message)) {
        $res['message'] = $message;
    }
    if (!empty($data)) {
        $res['data'] = $data;
    }

    return response($res, $code);
}
