<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

function writeLog($filename, $data) {
    $logDir = __DIR__ . "/logs/";
    if (!file_exists($logDir)) mkdir($logDir, 0777, true);
    file_put_contents($logDir . $filename, "[" . date("Y-m-d H:i:s") . "] " . $data . PHP_EOL, FILE_APPEND);
}

$data = json_decode(file_get_contents("php://input"), true);
writeLog("electricity.log", "PURCHASE REQUEST: " . json_encode($data));

$token     = $data['token']     ?? '';
$meter     = $data['meter']     ?? '';
$serviceID = $data['serviceID'] ?? '';
$type      = $data['type']      ?? '';
$amount    = $data['amount']    ?? 0;
$phone     = $data['phone']     ?? '';
$pin       = $data['pin']       ?? '';

if (!$token || !$meter || !$serviceID || !$type || !$amount || !$pin) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

// Verify user
$verify = verifyUserToken($conn, $token);
if (!$verify['success']) {
    echo json_encode($verify);
    exit;
}

$user      = $verify['user'];
$userId    = $user['email'];
$email     = $user['email'];
$userPhone = $user['phone'];

// Verify PIN
if ($pin !== "fingerprint") {
    if (md5($pin) !== $user['pin']) {
        echo json_encode(["success" => false, "message" => "Invalid PIN"]);
        exit;
    }
}

// Check wallet
$walletQ = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$userId'");
$wallet  = mysqli_fetch_assoc($walletQ);

if (!$wallet || $wallet['balance'] < $amount) {
    echo json_encode(["success" => false, "message" => "Insufficient balance"]);
    exit;
}

// Get API config
$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name='vtpass'");
$api  = mysqli_fetch_assoc($apiQ);

if (!$api) {
    echo json_encode(["success" => false, "message" => "No active API configured"]);
    exit;
}

$url = rtrim($api['api_url'], '/') . "/api/pay";

// Generate VTPass-compliant request ID (≥20 chars)
$requestId = date('YmdHis') . "ELC" . rand(100000, 999999);

// Deduct wallet
$newBalance = $wallet['balance'] - $amount;
mysqli_query($conn, "UPDATE wallet_tbl SET balance='$newBalance' WHERE user_id='$userId'");

// VTPass payload
$params = [
    "request_id"     => $requestId,
    "serviceID"      => strtolower($serviceID),
    "billersCode"    => $meter,
    "variation_code" => strtolower($type),
    "amount"         => (int)$amount,
    "phone"          => $phone ?: $userPhone
];

writeLog("electricity.log", "REQUEST PAYLOAD: " . json_encode($params));

// cURL
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($params),
    CURLOPT_USERPWD        => $api['api_key'] . ':' . $api['secret'],
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($curl);
$err      = curl_error($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

$res = json_decode($response, true);

writeLog("electricity.log", "==============================");
writeLog("electricity.log", "REQUEST_ID: $requestId");
writeLog("electricity.log", "HTTP_CODE: $httpCode");
writeLog("electricity.log", "CURL_ERROR: " . ($err ?: "NONE"));
writeLog("electricity.log", "RAW_RESPONSE: " . $response);
writeLog("electricity.log", "==============================");

// Rollback on failure
if ($err || !$res) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
    echo json_encode(["success" => false, "message" => "API connection error: " . ($err ?: "No response")]);
    exit;
}

$vtCode = $res['code'] ?? '';
$status = ($vtCode === '000');

if (!$status) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
    echo json_encode([
        "success" => false,
        "message" => "Transaction failed: " . ($res['response_description'] ?? $vtCode),
        "api_response" => $res
    ]);
    exit;
}

// Extract electricity token from correct VTPass response path
// Token is in content.transactions.token or top-level purchased_code
$rawToken = $res['content']['transactions']['token']
         ?? $res['content']['transactions']['purchased_code']
         ?? $res['purchased_code']
         ?? $res['token']
         ?? '';

// Clean token: VTPass sometimes returns "Token : 1234-5678-..." format
$tokenCode = preg_replace('/[^0-9\-]/', '', $rawToken);
$tokenCode = trim($tokenCode, '-');
if (!$tokenCode) {
    $tokenCode = $rawToken ?: "N/A";
}

$transactionId  = $res['content']['transactions']['transactionId'] ?? null;
$safeDesc       = mysqli_real_escape_string($conn, json_encode($res));
$safeTransId    = mysqli_real_escape_string($conn, $transactionId ?? '');
$safeToken      = mysqli_real_escape_string($conn, $tokenCode);

// Save transaction
mysqli_query($conn, "
    INSERT INTO transactions_tbl 
    (unique_element, amount, real_amount, email, phone, transaction_id, request_id, product_name, response_description, status, transaction_date, is_bill, our_commission)
    VALUES 
    ('$meter', '$amount', '$amount', '$email', '".($phone ?: $userPhone)."', '$safeTransId', '$requestId', 'Electricity ($serviceID)', '$safeDesc', 1, NOW(), 1, 0)
");

writeLog("electricity.log", "SUCCESS: token=$tokenCode transactionId=$transactionId");

echo json_encode([
    "success"        => true,
    "message"        => "Electricity purchase successful",
    "token"          => $tokenCode,
    "balance"        => $newBalance,
    "request_id"     => $requestId,
    "transaction_id" => $transactionId,
    "api_response"   => $res
]);
?>
