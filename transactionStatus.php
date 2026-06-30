<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data = json_decode(file_get_contents("php://input"), true);
$token     = $data['token']      ?? '';
$requestId = $data['request_id'] ?? '';

if (!$token || !$requestId) {
    echo json_encode(["success"=>false,"message"=>"token and request_id required"]);
    exit;
}

$verify = verifyUserToken($conn, $token);
if (!$verify['success']) { echo json_encode($verify); exit; }

// Local DB check first
$safe = mysqli_real_escape_string($conn, $requestId);
$q    = mysqli_query($conn, "SELECT * FROM transactions_tbl WHERE request_id='$safe' LIMIT 1");
$local = $q && mysqli_num_rows($q) > 0 ? mysqli_fetch_assoc($q) : null;

// VTPass requery
$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
$api  = mysqli_fetch_assoc($apiQ);
$url  = rtrim($api['api_url'],'/') . "/api/requery";

$curl = curl_init();
curl_setopt_array($curl,[
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(["request_id"=>$requestId]),
    CURLOPT_USERPWD => $api['api_key'].":".$api['secret'],
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT => 30,
]);
$res = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

$vtRes = $err ? ["error"=>$err] : json_decode($res, true);
echo json_encode(["success"=>true,"local"=>$local,"vtpass"=>$vtRes]);
