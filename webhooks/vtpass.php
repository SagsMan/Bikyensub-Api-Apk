<?php
/**
 * webhooks/vtpass.php — VTPass callback/webhook handler for Bikyensub
 * Endpoint: https://api.bikyensub.com.ng/webhooks/vtpass.php
 *
 * Accepts POST callbacks from VTPass, validates them,
 * updates transactions_tbl (status, transaction_id, request_id, response_description).
 *
 * transactions_tbl key columns:
 *   request_id, transaction_id, status, response_description
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include_once __DIR__ . '/../conn.php';

// ── Logger ────────────────────────────────────────────────────────────────────
function vtpLog($msg) {
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $ts = date('Y-m-d H:i:s');
    @file_put_contents($logDir . 'vtpass_webhook.log', "[$ts] $msg\n", FILE_APPEND);
}

function vtpJson($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── Read raw payload ──────────────────────────────────────────────────────────
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

vtpLog("RECEIVED: " . $raw);

if (empty($payload)) {
    vtpLog("ERROR: empty or invalid payload");
    vtpJson(['status' => 'error', 'message' => 'Invalid payload'], 400);
}

// ── Extract fields from VTPass callback ───────────────────────────────────────
// VTPass sends: requestId, transactionId, amount, response_description, status, content{}
$requestId   = $payload['requestId']
            ?? $payload['request_id']
            ?? ($payload['content']['transactions']['requestId'] ?? '');

$transId     = $payload['transactionId']
            ?? $payload['transaction_id']
            ?? ($payload['content']['transactions']['transactionId'] ?? '');

$vtpCode     = $payload['code']
            ?? $payload['status']
            ?? ($payload['content']['transactions']['status'] ?? '');

$vtpDesc     = $payload['response_description']
            ?? $payload['message']
            ?? ($payload['content']['transactions']['response_description'] ?? 'VTPass callback');

vtpLog("FIELDS: requestId=$requestId transId=$transId code=$vtpCode desc=$vtpDesc");

// ── Determine success ─────────────────────────────────────────────────────────
$isSuccess = strtolower($vtpCode) === '000'
          || strtolower($vtpCode) === 'delivered'
          || strtolower($vtpCode) === 'successful'
          || strtolower($vtpCode) === 'success';

$statusInt = $isSuccess ? 1 : 0;

vtpLog("STATUS: " . ($isSuccess ? "SUCCESS" : "FAILED/PENDING") . " (code=$vtpCode)");

if (empty($requestId)) {
    vtpLog("ERROR: no requestId in payload");
    vtpJson(['status' => 'error', 'message' => 'requestId missing'], 400);
}

// ── Update transactions_tbl ───────────────────────────────────────────────────
$reqSafe  = mysqli_real_escape_string($conn, $requestId);
$txnSafe  = mysqli_real_escape_string($conn, $transId);
$descSafe = mysqli_real_escape_string($conn, json_encode($payload));

// Check if transaction exists
$check = mysqli_query($conn,
    "SELECT id, status FROM transactions_tbl WHERE request_id='$reqSafe' LIMIT 1"
);

if (!$check || mysqli_num_rows($check) === 0) {
    vtpLog("WARNING: no transaction found for request_id=$requestId");
    // Still return 200 so VTPass stops retrying unknown refs
    vtpJson(['status' => 'ok', 'message' => 'Acknowledged — transaction not found locally']);
}

$existingRow = mysqli_fetch_assoc($check);

// Only update if currently pending (status=0) or forced by success
if ($existingRow['status'] == 0 || $isSuccess) {
    $update = mysqli_query($conn,
        "UPDATE transactions_tbl
         SET status              = '$statusInt',
             transaction_id      = '$txnSafe',
             response_description = '$descSafe'
         WHERE request_id = '$reqSafe'"
    );

    if ($update) {
        vtpLog("UPDATED: request_id=$requestId → status=$statusInt transId=$transId");
    } else {
        vtpLog("DB ERROR: " . mysqli_error($conn));
    }
} else {
    vtpLog("SKIP: transaction already finalized (status=" . $existingRow['status'] . ")");
}

vtpLog("DONE: request_id=$requestId status=$statusInt");

vtpJson([
    'status'         => 'ok',
    'message'        => 'Webhook processed',
    'request_id'     => $requestId,
    'transaction_id' => $transId,
    'payment_status' => $isSuccess ? 'successful' : 'pending_or_failed',
]);
