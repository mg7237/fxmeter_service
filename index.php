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

    // CODE FOR APPLE PLATFORM
    if ($request->platform == 'IOS') {

        if (empty($request->receipt_data)) {
            $valErr = true;
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $error[] = "Missing Receipt Data";
            $response['data'] = [];
        }

        // if (empty($request->env)) {
        //     $valErr = true;
        //     $response['is_error'] = 1;
        //     $response['subscription_status'] = 0;
        //     $error[] = "Missing Environment";
        //     $response['data'] = [];
        // } else if (!in_array($request->env, $__ENV)) {
        //     $valErr = true;
        //     $response['is_error'] = 1;
        //     $response['subscription_status'] = 0;
        //     $error[] = "Invalid Environment";
        //     $response['data'] = [];
        // }

        if (!$valErr) {
            $payLoad = [];
            $payLoad['receipt-data'] = $request->receipt_data;
            $payLoad['password'] = $__SECRET;
            $payLoad['exclude-old-transactions'] = true;

            $jsonPayload = json_encode($payLoad);

            // $ch = curl_init($__RECEIPT_VERIFY_URL[$request->env]);
            $ch = curl_init($__RECEIPT_VERIFY_URL['PROD']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            
            if (!empty($result)) {
                if ($result->status == 21007) {
                    $ch = curl_init($__RECEIPT_VERIFY_URL['TEST']);
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
                        if (!empty($result->latest_receipt_info[0]->expires_date_ms)) {
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
                            $response['error'] = [$result->status];
                            $response['data'] = $result;
                        }
                        
                    } else {
                        $response['is_error'] = 1;
                        $response['subscription_status'] = 0;
                        $response['error'] = ["Apps store failed to response."];
                        $response['data'] = [];
                    }

                } else {
                    $currentTime = time();
                    $currentTime = $currentTime * 1000;
                    if (!empty($result->latest_receipt_info[0]->expires_date_ms)) {
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
                        $response['error'] = [$result->status];
                        $response['data'] = $result;
                    }
                }
            } else {
                $response['is_error'] = 1;
                $response['subscription_status'] = 0;
                $response['error'] = ["Apps store failed to response."];
                $response['data'] = [];
            }
        } else {
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $response['error'] = $error;
            $response['data'] = [];
        }
        
    // CODE FOR GOOGLE PLATFORM
    } else if ($request->platform == 'ANDROID') {

        if (empty($request->package_name)) {
            $valErr = true;
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $error[] = "Missing Package Name";
            $response['data'] = [];
        }

        if (empty($request->product_id)) {
            $valErr = true;
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $error[] = "Missing Product ID";
            $response['data'] = [];
        }

        if (empty($request->token)) {
            $valErr = true;
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $error[] = "Missing Token";
            $response['data'] = [];
        }

        if (empty($request->access_token)) {
            $valErr = true;
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $error[] = "Missing Access Token";
            $response['data'] = [];
        }

        if (!$valErr) {

            $payLoad = [];
            $payLoad['packageName'] = $request->package_name;
            $payLoad['productId'] = $request->product_id;
            $payLoad['token'] = $request->token;

            $header[] = 'Authorization: Bearer '. $request->access_token;
            $header[] = 'Content-Type: application/json';

            $jsonPayload = json_encode($payLoad);

            // $ch = curl_init($__PURCHASE_VERIFY_URL . $request->package_name . '/purchases/products/' . $request->product_id . '/tokens/' . $request->token . '?key=AIzaSyAa8yy0GdcGPHdtD083HiGGx_S0vMPScDM');
            $ch = curl_init($__PURCHASE_VERIFY_URL . $request->package_name . '/purchases/products/' . $request->product_id . '/tokens/' . $request->token);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            
            if (!empty($result)) {
                if (isset($result->purchaseState)) {
                    if ($result->purchaseState == 0) {
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

        } else {
            $response['is_error'] = 1;
            $response['subscription_status'] = 0;
            $response['error'] = $error;
            $response['data'] = [];
        }
    }
    
} else {
    $response['is_error'] = 1;
    $response['subscription_status'] = 0;
    $response['error'] = ["Missing Payload"];
    $response['data'] = [];
}

header("Content-Type: application/json");
echo json_encode($response);