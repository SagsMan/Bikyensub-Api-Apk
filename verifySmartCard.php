<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once 'conn.php';

$data = json_decode(file_get_contents("php://input"), true);

$smartcard = $data['smartcard'] ?? '';
$serviceID = $data['serviceID'] ?? '';

if (!$smartcard || !$serviceID) {
    echo json_encode(["success" => false, "message" => "smartcard and serviceID are required"]);
    exit;
}

// Map common aliases to VTPass serviceIDs
function mapTvServiceID($serviceID) {
    $map = [
        'dstv'      => 'dstv',
        'gotv'      => 'gotv',
        'startimes' => 'startimes',
    ];
    return $map[strtolower($serviceID)] ?? strtolower($serviceID);
}

$vtServiceID = mapTvServiceID($serviceID);

// Get API settings
$apiQ = mysqli_query($conn, "SELECT * FROM api_settings WHERE api_name = 'vtpass'");
$api  = mysqli_fetch_assoc($apiQ);

if (!$api) {
    echo json_encode(["success" => false, "message" => "API not configured"]);
    exit;
}

$apiUrl = rtrim($api['api_url'], '/') . "/api/merchant-verify";
$apiKey = $api['api_key'];
$secret = $api['secret'];

// Payload
$params = [
    "billersCode" => $smartcard,
    "serviceID"   => $vtServiceID
];

// cURL — use Basic auth (consistent with all other VTPass endpoints)
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

$response = curl_exec($curl);
$err      = curl_error($curl);
curl_close($curl);

$res = json_decode($response, true);

if ($err || !$res) {
    echo json_encode(["success" => false, "message" => "Verification failed: " . ($err ?: "No response")]);
    exit;
}

// VTPass returns Customer_Name on success
$customerName = $res['content']['Customer_Name']
             ?? $res['content']['customer_name']
             ?? null;

$dueDate    = $res['content']['Due_Date']         ?? null;
$currentBouquet = $res['content']['Current_Bouquet'] ?? null;
$renewalAmount  = $res['content']['Renewal_Amount']  ?? null;

if (!$customerName) {
    echo json_encode([
        "success"      => false,
        "message"      => "Invalid smartcard number or unable to verify",
        "api_response" => $res
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Verification successful",
    "data"    => [
        "customer_name"   => $customerName,
        "due_date"        => $dueDate,
        "current_bouquet" => $currentBouquet,
        "renewal_amount"  => $renewalAmount,
        "raw"             => $res
    ]
]);
?>
