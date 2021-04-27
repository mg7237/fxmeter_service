<?php

require 'auth.php';
require 'db.php';
require 'function.php';

$response = [];

require_auth();

$inputs = file_get_contents('php://input');
$request = json_decode($inputs);

$valErr = false;
$error = [];

if (!empty($request)) {
    
    if (empty($request->platform)) {
        $valErr = true;
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $error[] = "Missing Platform";
        $response['data'] = [];
    } else if (!in_array($request->platform, $__PLATFORM)) {
        $valErr = true;
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $error[] = "Invalid Platform";
        $response['data'] = [];
    }
    
    if (empty($request->receipt_data)) {
        $valErr = true;
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $error[] = "Missing Receipt Data";
        $response['data'] = [];
    }

    if (empty($request->env)) {
        $valErr = true;
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $error[] = "Missing Environment";
        $response['data'] = [];
    } else if (!in_array($request->env, $__ENV)) {
        $valErr = true;
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $error[] = "Invalid Environment";
        $response['data'] = [];
    }
    
    if (!$valErr) {

        // CODE FOR APPLE PLATFORM
        if ($request->platform == 'IOS') {
            $payLoad = [];
            $payLoad['receipt-data'] = $request->receipt_data;
            $payLoad['password'] = $__SECRET;
            $payLoad['exclude-old-transactions'] = true;

            $jsonPayload = json_encode($payLoad);

            $ch = curl_init($__RECEIPT_VERIFY_URL[$request->env]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            
            if (!empty($result)) {
                $currentTime = time();
                $currentTime = $currentTime * 1000;
                $expireDate = (int) $result->latest_receipt_info[0]->expires_date_ms;
                if ($expireDate >= $currentTime) {
                    $response['is_error'] = 0;
                    $response['subscription_status'] = 1;
                    $response['error'] = [];
                    $response['data'] = $result;
                } else {
                    $response['is_error'] = 0;
                    $response['subscription_status'] = 0;
                    $response['error'] = [];
                    $response['data'] = $result;
                }
            } else {
                $response['is_error'] = 1;
                $response['subscription_status'] = 0;
                $response['error'] = ["Apps store failed to response."];
                $response['data'] = [];
            }
        } else if ($request->platform == 'ANDROID') {
            // CODE FOR GOOGLE PLATFORM
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $response['error'] = ["Play store failed to response."];
            $response['data'] = [];
        }

    } else {
        $response['is_error'] = 1;
        $response['subscription_status'] = 0;
        $response['error'] = $error;
        $response['data'] = [];
    }
    
} else {
    $response['is_error'] = 1;
    $response['subscription_status'] = 0;
    $response['error'] = ["Missing Payload"];
    $response['data'] = [];
}

header("Content-Type: application/json");
echo json_encode($response);