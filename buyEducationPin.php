<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data = json_decode(file_get_contents("php://input"), true);

$token     = $data['token'] ?? '';
$service   = strtolower($data['service'] ?? '');
$number    = $data['number'] ?? '';
$pin       = $data['pin'] ?? '';
$profileId = $data['profileId'] ?? '';

if (!$token || !$service || !$pin) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

$verify = verifyUserToken($conn, $token);
if (!$verify['success']) {
    echo json_encode($verify);
    exit;
}

$user   = $verify['user'];
$userId = $user['email'];
$email  = $user['email'];

if ($pin !== "fingerprint") {
    if (md5($pin) !== $user['pin']) {
        echo json_encode(["success" => false, "message" => "Invalid PIN"]);
        exit;
    }
}

$services = [
    "waec"   => ["variation" => "waecdirect", "amount" => 3500],
    "neco"   => ["variation" => "neco",       "amount" => 1200],
    "nabteb" => ["variation" => "nabteb",     "amount" => 1000],
    "nbais"  => ["variation" => "nbais",      "amount" => 1000],
    "jamb"   => ["variation" => "utme",       "amount" => 7000]
];

if (!isset($services[$service])) {
    echo json_encode(["success" => false, "message" => "Service unavailable"]);
    exit;
}

$variation = $services[$service]['variation'];
$amount    = $services[$service]['amount'];

if ($service === "jamb" && !$profileId) {
    echo json_encode(["success" => false, "message" => "JAMB Profile ID required"]);
    exit;
}

$walletQ = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$userId'");
$wallet  = mysqli_fetch_assoc($walletQ);

if (!$wallet || $wallet['balance'] < $amount) {
    echo json_encode(["success" => false, "message" => "Insufficient balance"]);
    exit;
}

$newBalance = $wallet['balance'] - $amount;
mysqli_query($conn, "UPDATE wallet_tbl SET balance='$newBalance' WHERE user_id='$userId'");

$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name = 'vtpass' LIMIT 1");
$api  = mysqli_fetch_assoc($apiQ);

if (!$api) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
    echo json_encode(["success" => false, "message" => "No active API"]);
    exit;
}

$apiUrl = $api['api_url'];
$apiKey = $api['api_key'];
$secret = $api['secret'];

// request_id >= 20 chars (VTPass requirement)
$requestId = date('YmdHis') . 'EDU' . rand(100000, 999999);

$params = [
    "request_id"     => $requestId,
    "serviceID"      => $service,
    "variation_code" => $variation,
    "amount"         => $amount,
    "phone"          => $number ?: "08000000000"
];

// JAMB also requires billersCode (profile ID)
if ($service === "jamb") {
    $params["billersCode"] = $profileId;
}

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($params),
    CURLOPT_USERPWD        => "$apiKey:$secret",
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT        => 30,
]);

$apiResponse = curl_exec($curl);
$curlError   = curl_error($curl);
$httpCode    = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$res = json_decode($apiResponse, true);

if ($curlError || !$res) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
    echo json_encode([
        "success"      => false,
        "message"      => "Service unavailable",
        "raw_response" => $apiResponse,
        "http_code"    => $httpCode
    ]);
    exit;
}

$status = strtolower($res['code'] ?? '') === "000";

if (!$status) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
}

// Extract PIN(s)
$pins = [];

if ($service === "jamb") {
    $purchased = $res['purchased_code'] ?? $res['Pin'] ?? null;
    if ($purchased) {
        $pins[] = ["Pin" => $purchased];
    }
} else {
    $pins = $res['content']['transactions']['cards'] ?? [];
}

$transactionId = $res['content']['transactions']['transactionId'] ?? null;
$productName   = strtoupper($service) . " PIN";
$responseDesc  = mysqli_real_escape_string($conn, json_encode($res));

mysqli_query($conn, "
    INSERT INTO transactions_tbl 
    (unique_element, amount, real_amount, email, phone, transaction_id, request_id, product_name, response_description, status, transaction_date, is_bill, our_commission)
    VALUES 
    ('$number', '$amount', '$amount', '$email', '$number', '$transactionId', '$requestId', '$productName', '$responseDesc', '" . ($status ? 1 : 0) . "', NOW(), 1, 0)
");

echo json_encode([
    "success"      => $status,
    "message"      => $status ? "PIN generated successfully" : "Transaction failed: " . ($res['response_description'] ?? 'FAILED'),
    "balance"      => $status ? $newBalance : $wallet['balance'],
    "request_id"   => $requestId,
    "pins"         => $pins,
    "api_response" => $res
]);
?>
