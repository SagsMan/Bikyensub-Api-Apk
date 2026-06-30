<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data = json_decode(file_get_contents("php://input"), true);
$token     = $data['token']     ?? '';
$serviceID = $data['serviceID'] ?? 'dstv';

if (!$token) { echo json_encode(["success"=>false,"message"=>"token required"]); exit; }

$verify = verifyUserToken($conn, $token);
if (!$verify['success']) { echo json_encode($verify); exit; }

$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
$api  = mysqli_fetch_assoc($apiQ);
$safe = urlencode($serviceID);
$url  = rtrim($api['api_url'],'/') . "/api/service-variations?serviceID=$safe";

$curl = curl_init();
curl_setopt_array($curl,[
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $api['api_key'].":".$api['secret'],
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT => 30,
]);
$res = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) { echo json_encode(["success"=>false,"message"=>$err]); exit; }
echo json_encode(["success"=>true,"serviceID"=>$serviceID,"data"=>json_decode($res,true)]);
