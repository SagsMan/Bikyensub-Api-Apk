<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data      = json_decode(file_get_contents("php://input"), true);
$token     = $data['token']     ?? '';
$serviceID = $data['serviceID'] ?? 'mtn-data';

if (!$token) {
    echo json_encode(["success" => false, "message" => "token required"]);
    exit;
}

$verify = verifyUserToken($conn, $token);
if (!$verify['success']) {
    echo json_encode($verify);
    exit;
}

// Map to VTPass data serviceID
function mapDataServiceID($serviceID) {
    $map = [
        'mtn'           => 'mtn-data',
        'mtn-data'      => 'mtn-data',
        'airtel'        => 'airtel-data',
        'airtel-data'   => 'airtel-data',
        'glo'           => 'glo-data',
        'glo-data'      => 'glo-data',
        '9mobile'       => 'etisalat-data',
        '9mobile-data'  => 'etisalat-data',
        'etisalat'      => 'etisalat-data',
        'etisalat-data' => 'etisalat-data',
    ];
    return $map[strtolower($serviceID)] ?? strtolower($serviceID);
}

$vtServiceID = mapDataServiceID($serviceID);

$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass' LIMIT 1");
$api  = mysqli_fetch_assoc($apiQ);

if (!$api) {
    echo json_encode(["success" => false, "message" => "API not configured"]);
    exit;
}

$safe = urlencode($vtServiceID);
$url  = rtrim($api['api_url'], '/') . "/api/service-variations?serviceID=$safe";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => $api['api_key'] . ":" . $api['secret'],
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT        => 30,
]);
$res = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(["success" => false, "message" => "API error: $err"]);
    exit;
}

echo json_encode([
    "success"   => true,
    "serviceID" => $vtServiceID,
    "data"      => json_decode($res, true)
]);
?>
