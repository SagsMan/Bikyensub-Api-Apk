<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data = json_decode(file_get_contents("php://input"), true);
$token     = $data['token']     ?? '';
$smartcard = $data['smartcard'] ?? '';
$serviceID = $data['serviceID'] ?? '';

if (!$token || !$smartcard || !$serviceID) {
    echo json_encode(["success"=>false,"message"=>"token, smartcard, serviceID required"]);
    exit;
}

$verify = verifyUserToken($conn, $token);
if (!$verify['success']) { echo json_encode($verify); exit; }

$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
$api  = mysqli_fetch_assoc($apiQ);
$url  = rtrim($api['api_url'],'/') . "/api/merchant-verify";

$params = ["billersCode"=>$smartcard,"serviceID"=>$serviceID,"type"=>"basic"];
$curl = curl_init();
curl_setopt_array($curl,[
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($params),
    CURLOPT_USERPWD => $api['api_key'].":".$api['secret'],
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT => 30,
]);
$res = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) { echo json_encode(["success"=>false,"message"=>"cURL error: $err"]); exit; }
$vtRes = json_decode($res, true);
echo json_encode(["success"=>true,"data"=>$vtRes]);
