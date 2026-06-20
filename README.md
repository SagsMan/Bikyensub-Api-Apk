# Bikyensub APK REST API

A complete PHP REST API for the Bikyensub VTU (Virtual Top-Up) mobile application.  
Base URL: `https://api.bikyensub.com.ng/api.php?action=<ACTION>`

---

## Authentication

All protected endpoints require a `token` passed via one of:
- `Authorization: Bearer <token>` header
- `X-API-Token: <token>` header
- `token` field in the JSON body or query string

Obtain a token by calling the `login` action.

---

## All Endpoints

### Health
| Action | Method | Auth | Description |
|--------|--------|------|-------------|
| `health` / `ping` | GET | No | API health check |

**Response:**
```json
{
  "status": "success",
  "data": { "message": "Bikyensub API is running", "version": "2.0", "provider": "PaymentPoint", "time": "..." }
}
```

---

### Auth / User

#### Register
**POST** `?action=register`

**Request body:**
```json
{
  "email": "user@example.com",
  "password": "secret",
  "sname": "Tunde",
  "oname": "Bello",
  "phone": "08012345678",
  "pin": "1234",
  "state": "Lagos",
  "referal": "<referral_code>"
}
```
**Response:**
```json
{ "status": "success", "data": { "message": "Registration successful. Please submit your BVN/NIN via the KYC section to activate your virtual account." } }
```

---

#### Login
**POST** `?action=login`

**Request body:**
```json
{ "email": "user@example.com", "password": "secret" }
```
**Response:**
```json
{
  "status": "success",
  "data": {
    "token": "abc123...",
    "id": 1,
    "email": "user@example.com",
    "sname": "Tunde",
    "oname": "Bello",
    "phone": "08012345678",
    "admin_role": 0,
    "wallet_balance": 5000.00,
    "haspin": true,
    "finger": false,
    "has_account": true,
    "acc_no": "1234567890",
    "bank_name": "Palmpay",
    "acc_name": "Tunde Bello"
  }
}
```

---

#### Verify Token
**GET/POST** `?action=verify_token`

**Request:** `token` in body/header/query  
**Response:**
```json
{
  "status": "success",
  "data": {
    "valid": true,
    "user_id": 1,
    "email": "user@example.com",
    "name": "Tunde Bello",
    "haspin": true,
    "finger": false,
    "wallet_balance": 5000.00,
    "has_account": true,
    "acc_no": "1234567890",
    "bank_name": "Palmpay",
    "acc_name": "Tunde Bello",
    "accounts": [...]
  }
}
```

---

#### Profile / Init
**GET** `?action=profile` or `?action=init`

Returns full user profile including referral code, KYC status, unread notification count, and virtual accounts.

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "email": "user@example.com",
    "sname": "Tunde",
    "oname": "Bello",
    "phone": "08012345678",
    "referral_code": "abc123",
    "referral_link": "https://bikyensub.com.ng/easyfinder/dashboard/register?join_with_referal=abc123",
    "wallet_balance": 5000.00,
    "has_account": true,
    "acc_no": "1234567890",
    "bank_name": "Palmpay",
    "accounts": [...],
    "unread_count": 3,
    "has_bvn": true,
    "has_nin": false,
    "kyc_complete": true,
    "finger": false,
    "haspin": true
  }
}
```

---

#### Change Password
**POST** `?action=change_password` (Auth required)

```json
{ "old_password": "oldpass", "new_password": "newpass" }
```

---

#### Check Fingerprint
**GET/POST** `?action=check_fingerprint`

```json
{ "email": "user@example.com" }
```

---

#### Toggle Fingerprint
**POST** `?action=toggle_fingerprint` (Auth required)

---

#### Set PIN
**POST** `?action=set_pin` (Auth required)

```json
{ "pin": "1234" }
```

#### Change PIN
**POST** `?action=change_pin` (Auth required)

```json
{ "old_pin": "1234", "new_pin": "5678" }
```

---

### Wallet

#### Get Balance
**GET** `?action=wallet` (Auth required)

**Response:** `{ "balance": 5000.00, "email": "..." }`

---

#### Wallet History
**GET** `?action=wallet_history` (Auth required)

---

#### Transactions
**GET** `?action=transactions` (Auth required)

**Response:**
```json
{
  "status": "success",
  "data": {
    "transactions": [
      { "id": 1, "title": "Airtime Recharge", "phone": "08012345678", "date": "2026-06-20", "amount": "200", "status": 1, "subtitle": "Successful" }
    ]
  }
}
```

---

#### Dashboard Stats
**GET** `?action=dashboard_stats` (Auth required)

Returns `wallet_balance`, `total_transactions`, `success_transactions`, `failed_transactions`, `notifications_count`, `referral_count`, virtual account details.

---

### KYC (BVN / NIN)

#### Submit KYC
**POST** `?action=submit_kyc` (Auth required)

```json
{ "bvn": "12345678901" }
```
or
```json
{ "nin": "12345678901" }
```
or both. Responds immediately and generates virtual account in background.

---

#### Get KYC Status
**GET** `?action=get_kyc_status` (Auth required)

**Response:**
```json
{
  "status": "success",
  "data": {
    "kyc_complete": true,
    "has_bvn": true,
    "has_nin": false,
    "has_account": true,
    "account_number": "1234567890",
    "bank_name": "Palmpay",
    "account_name": "Tunde Bello"
  }
}
```

---

### Virtual Accounts (PaymentPoint)

#### Funding Accounts
**GET** `?action=funding_accounts` (Auth required)

Returns user's Palmpay + Opay virtual account numbers.

---

#### Generate Virtual Account
**POST** `?action=generate_account` (Auth required)

Requires KYC (BVN or NIN) to be submitted first.

---

#### Verify Account
**GET** `?action=verify_account` (Auth required)

---

### VTU Services

#### Buy Airtime
**POST** `?action=buy_airtime` (Auth required)

```json
{
  "amount": 200,
  "number": "08012345678",
  "network": "mtn",
  "pin": "1234"
}
```
Networks: `mtn`, `airtel`, `glo`, `9mobile` / `etisalat`

---

#### Buy Data
**POST** `?action=buy_data` (Auth required)

```json
{
  "amount": 500,
  "number": "08012345678",
  "serviceID": "mtn-data",
  "variation": "mtn-10mb-100",
  "pin": "1234"
}
```

---

#### Data Plans
**GET** `?action=data_plans&serviceID=mtn-data`

---

#### Data Types
**GET** `?action=data_types&serviceID=mtn-data`

Networks: `mtn-data`, `glo-data`, `airtel-data`, `etisalat-data`

---

#### Other Data Plans
**GET** `?action=other_data_plans&plan_id=<plan_types.id>`

---

#### Buy Other Data
**POST** `?action=buy_other_data` (Auth required)

```json
{
  "number": "08012345678",
  "plan_id": "42",
  "pin": "1234"
}
```

---

### Notifications (In-App)

#### Get Notifications
**GET** `?action=notifications` (Auth required)

**Response:**
```json
{
  "status": "success",
  "data": {
    "notifications": [
      { "id": 1, "title": "Wallet Credited", "message": "N500 added...", "type": "success", "is_read": false, "created_at": "..." }
    ],
    "unread_count": 2
  }
}
```

---

#### Get Unread Count
**GET** `?action=get_unread_count` (Auth required)

---

#### Mark Notification Read
**POST** `?action=mark_notification_read` (Auth required)

```json
{ "notification_id": 5 }
```

---

#### Mark All Read
**POST** `?action=mark_all_notifications_read` (Auth required)

---

### Push Notifications (FCM)

#### Save Device Token
**POST** `saveDeviceToken.php`

Call this after login to enable push notifications.

```json
{
  "token": "<auth_token>",
  "fcm_token": "<firebase_push_token>",
  "platform": "android"
}
```

---

#### Broadcast to All Devices (Admin)
**POST** `broadcastNotification.php`

```json
{
  "admin_key": "BikyenSubAdmin2026!",
  "title": "New Deal!",
  "body": "MTN 1GB for N200 only!",
  "platform": "all",
  "data": { "screen": "DataPlans" }
}
```

---

#### Send Push to Specific User (Admin)
**POST** `sendPushToUser.php`

```json
{
  "admin_key": "BikyenSubAdmin2026!",
  "email": "user@example.com",
  "title": "Message",
  "body": "Your order is ready",
  "data": { "screen": "Wallet" }
}
```

---

### Referral

#### Get Referral Stats
**GET** `?action=referral` (Auth required)

**Response:**
```json
{
  "status": "success",
  "data": {
    "referral_code": "abc123",
    "referral_link": "https://bikyensub.com.ng/easyfinder/dashboard/register?join_with_referal=abc123",
    "total_referred": 5,
    "total_earnings": 250.00,
    "referred_users": [...],
    "share_message": "Join Bikyensub and earn..."
  }
}
```

---

### Webhook (PaymentPoint)

**POST** `webhook.php`

Called automatically by PaymentPoint when a user funds their wallet. Credits the wallet, saves to payment_history_tbl, creates an in-app notification, and sends an FCM push notification.

---

## Error Responses

All errors return:
```json
{ "status": "error", "message": "Human-readable error message" }
```

HTTP status codes: `400` (bad request), `401` (unauthorized), `404` (not found), `405` (method not allowed), `409` (conflict), `422` (unprocessable), `503` (service unavailable).

---

## Database Setup

Run `setup_tables.sql` once in phpMyAdmin to create all required tables and add missing columns.

---

## Firebase Setup (Push Notifications)

1. Upload `/home/eduowrav/firebase_service_account.json` from your Firebase Console
2. Firebase project: `vtu-apps-5c6af`
3. The `fcm_helper.php` uses Service Account JWT auth (no legacy server key needed)

---

## Admin Keys

| Key | Value |
|-----|-------|
| Broadcast / Send Push | `BikyenSubAdmin2026!` |

---

## Stack

- PHP 7.4+
- MySQL / MariaDB
- PaymentPoint (Palmpay + Opay virtual accounts)
- Firebase Cloud Messaging (FCM HTTP v1)
- VTpass (airtime, data, electricity, TV)
