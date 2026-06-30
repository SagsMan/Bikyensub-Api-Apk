<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';
require_once 'transactionToken.php';

$data = json_decode(file_get_contents("php://input"), true);

$token     = $data['token']     ?? '';
$amount    = $data['amount']    ?? 0;
$smartcard = $data['smartcard'] ?? '';
$serviceID = $data['serviceID'] ?? '';   // gotv, dstv, startimes
$variation = $data['variation'] ?? '';   // bouquet variation code
$pin       = $data['pin']       ?? '';
$phone     = $data['phone']     ?? '';

// Map common aliases to VTPass serviceIDs
function mapTvServiceID($serviceID) {
    $map = [
        'dstv'      => 'dstv',
        'gotv'      => 'gotv',
        'startimes' => 'startimes',
        'go-tv'     => 'gotv',
        'star-times'=> 'startimes',
    ];
    return $map[strtolower($serviceID)] ?? strtolower($serviceID);
}

$vtServiceID = mapTvServiceID($serviceID);

// Validation
if (!$token || !$amount || !$smartcard || !$serviceID || !$variation || !$pin) {
    echo json_encode(["success" => false, "message" => "All fields required"]);
    exit;
}

// Verify user token
$verify = verifyUserToken($conn, $token);
if (!$verify['success']) {
    echo json_encode($verify);
    exit;
}

$user      = $verify['user'];
$userId    = $user['email'];
$email     = $user['email'];
$userPhone = $user['phone'];

// PIN check
if ($pin !== "fingerprint") {
    if (md5($pin) !== $user['pin']) {
        echo json_encode(["success" => false, "message" => "Invalid PIN"]);
        exit;
    }
}

// Wallet check
$walletQ = mysqli_query($conn, "SELECT balance FROM wallet_tbl WHERE user_id='$userId'");
$wallet  = mysqli_fetch_assoc($walletQ);

if (!$wallet || $wallet['balance'] < $amount) {
    echo json_encode(["success" => false, "message" => "Insufficient balance"]);
    exit;
}

// Get API config
$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name = 'vtpass'");
$api  = mysqli_fetch_assoc($apiQ);

if (!$api) {
    echo json_encode(["success" => false, "message" => "No active API configured"]);
    exit;
}

$apiUrl = rtrim($api['api_url'], '/') . "/api/pay";
$apiKey = $api['api_key'];
$secret = $api['secret'];

// Generate VTPass-compliant request ID (≥20 chars, date-based)
$requestId = date('YmdHis') . "TVS" . rand(100000, 999999);

// Deduct wallet BEFORE request
$newBalance = $wallet['balance'] - $amount;
mysqli_query($conn, "UPDATE wallet_tbl SET balance='$newBalance' WHERE user_id='$userId'");

// VTPass payload
$subscriberPhone = $phone ?: $userPhone;
$params = [
    "request_id"     => $requestId,
    "serviceID"      => $vtServiceID,
    "billersCode"    => $smartcard,
    "variation_code" => $variation,
    "amount"         => (int)$amount,
    "phone"          => $subscriberPhone
];

// cURL request
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

// API failure → rollback
if ($curlError || !$res) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
    echo json_encode(["success" => false, "message" => "API Error: " . ($curlError ?: "No response")]);
    exit;
}

// VTPass success: code "000"
$vtCode = $res['code'] ?? '';
$status = ($vtCode === '000');

// Failed → rollback
if (!$status) {
    mysqli_query($conn, "UPDATE wallet_tbl SET balance='{$wallet['balance']}' WHERE user_id='$userId'");
}

// Extract response details
$transactionId = $res['content']['transactions']['transactionId'] ?? null;
$productName   = $res['content']['transactions']['product_name'] ?? (strtoupper($serviceID) . " Subscription");
$safeDesc      = mysqli_real_escape_string($conn, json_encode($res));
$safeTransId   = mysqli_real_escape_string($conn, $transactionId ?? '');

// Save transaction
mysqli_query($conn, "
    INSERT INTO transactions_tbl 
    (unique_element, amount, real_amount, email, phone, transaction_id, request_id, product_name, response_description, status, transaction_date, is_bill, our_commission)
    VALUES 
    ('$smartcard', '$amount', '$amount', '$email', '$subscriberPhone', '$safeTransId', '$requestId', '$productName', '$safeDesc', '".($status ? 1 : 0)."', NOW(), 1, 0)
");

echo json_encode([
    "success"        => $status,
    "message"        => $status ? "TV subscription successful" : ("Transaction failed: " . ($res['response_description'] ?? $vtCode)),
    "balance"        => $status ? $newBalance : $wallet['balance'],
    "request_id"     => $requestId,
    "transaction_id" => $transactionId,
    "api_response"   => $res
]);
?>
