# Bikyensub APK — Complete API Documentation
### For Frontend / Mobile Developers

---

## 🔑 Test Credentials (Use for Development)

| Field | Value |
|-------|-------|
| Email | `softwareclone100@gmail.com` |
| Password | `123456` |
| Live Token (refresh via login) | Call `login` to get a fresh token |
| Base URL | `https://api.bikyensub.com.ng` |
| Main Router | `https://api.bikyensub.com.ng/api.php?action=<ACTION>` |

---

## 📋 Table of Contents

1. [How to Authenticate](#authentication)
2. [Health / Ping](#1-health-check)
3. [Register](#2-register)
4. [Login](#3-login)
5. [Verify Token](#4-verify-token)
6. [Profile / Init](#5-profile--init)
7. [Wallet Balance](#6-wallet-balance)
8. [Wallet History](#7-wallet-history)
9. [Transactions](#8-transactions)
10. [Dashboard Stats](#9-dashboard-stats)
11. [KYC — Submit BVN/NIN](#10-submit-kyc)
12. [KYC — Get Status](#11-get-kyc-status)
13. [Funding Accounts (Virtual Account)](#12-funding-accounts)
14. [Generate Virtual Account](#13-generate-virtual-account)
15. [Verify Account](#14-verify-account)
16. [Buy Airtime](#15-buy-airtime)
17. [Buy Data (VTpass)](#16-buy-data-vtpass)
18. [Data Plans](#17-data-plans)
19. [Data Types (Other Networks)](#18-data-types)
20. [Other Data Plans](#19-other-data-plans)
21. [Buy Other Data](#20-buy-other-data)
22. [Get Notifications](#21-get-notifications)
23. [Get Unread Count](#22-get-unread-count)
24. [Mark Notification Read](#23-mark-notification-read)
25. [Mark All Read](#24-mark-all-notifications-read)
26. [Referral Stats](#25-referral-stats)
27. [Check Fingerprint](#26-check-fingerprint)
28. [Toggle Fingerprint](#27-toggle-fingerprint)
29. [Set PIN](#28-set-pin)
30. [Change PIN](#29-change-pin)
31. [Change Password](#30-change-password)
32. [Save Device Token (FCM Push)](#31-save-device-token-fcm)
33. [Broadcast Notification (Admin)](#32-broadcast-notification-admin)
34. [Send Push to User (Admin)](#33-send-push-to-user-admin)
35. [PaymentPoint Webhook](#34-paymentpoint-webhook)
36. [Error Responses](#error-responses)
37. [Response Wrapper](#response-wrapper)

---

## Authentication

All protected endpoints require a token. Send it using **any** of these methods:

```
Header:  Authorization: Bearer <token>
Header:  X-API-Token: <token>
Body:    { "token": "<token>" }
Query:   ?token=<token>
```

---

## 1. Health Check

> No auth required. Use to check if the API is up.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=health
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "Bikyensub API is running",
    "version": "2.0",
    "provider": "PaymentPoint",
    "time": "2026-06-20 08:16:18"
  }
}
```

---

## 2. Register

> No auth required.

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=register
Content-Type: application/json
```

**Request Body**
```json
{
  "email": "user@example.com",
  "password": "YourPassword1",
  "sname": "Tunde",
  "oname": "Bello",
  "phone": "08012345678",
  "pin": "1234",
  "state": "Lagos",
  "referal": "e616a466e3267cb7996eebbc1f56ce30"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `email` | ✅ | User email address |
| `password` | ✅ | Account password |
| `sname` | ✅ | Surname / Last name |
| `oname` | ❌ | Other name / First name |
| `phone` | ✅ | Phone number |
| `pin` | ❌ | 4-digit transaction PIN (default: `0000`) |
| `state` | ❌ | State |
| `referal` | ❌ | Referral code of the person who invited the user |

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "Registration successful. Please submit your BVN/NIN via the KYC section to activate your virtual account."
  }
}
```

---

## 3. Login

> No auth required. Returns the auth token for all subsequent requests.

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=login
Content-Type: application/json
```

**Request Body**
```json
{
  "email": "softwareclone100@gmail.com",
  "password": "123456"
}
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "token": "f1d2361866eadefeb840e8bdef65c89ac52cab58d93fa0176ce86c5af3872e1a",
    "id": "439",
    "email": "softwareclone100@gmail.com",
    "sname": "Mahmud",
    "oname": "Muhammad",
    "phone": "08160327173",
    "admin_role": "1,2,3",
    "wallet_balance": 30,
    "haspin": true,
    "finger": false,
    "has_account": true,
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)"
  }
}
```

> 💾 **Save the `token` — pass it in the `Authorization` header for all protected endpoints.**

---

## 4. Verify Token

> Checks if a token is still valid. Use on app startup to restore session.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=verify_token
Authorization: Bearer f1d2361866eadefeb840e8bdef65c89ac52cab58d93fa0176ce86c5af3872e1a
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "valid": true,
    "user_id": "439",
    "email": "softwareclone100@gmail.com",
    "name": "Mahmud Muhammad",
    "phone": "08160327173",
    "haspin": true,
    "finger": false,
    "wallet_balance": 30,
    "has_account": true,
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ]
  }
}
```

---

## 5. Profile / Init

> Full user profile — call this on every app open after verifying token.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=profile
Authorization: Bearer f1d2361866eadefeb840e8bdef65c89ac52cab58d93fa0176ce86c5af3872e1a
```

> Also works as `?action=init`

**Live Response**
```json
{
  "status": "success",
  "data": {
    "id": "439",
    "email": "softwareclone100@gmail.com",
    "sname": "Mahmud",
    "oname": "Muhammad",
    "phone": "08160327173",
    "state": "Kano",
    "admin_role": "1,2,3",
    "super_admin": "1",
    "referral_code": "e616a466e3267cb7996eebbc1f56ce30",
    "referral_link": "https://bikyensub.com.ng/easyfinder/dashboard/register?join_with_referal=e616a466e3267cb7996eebbc1f56ce30",
    "wallet_balance": 30,
    "has_account": true,
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ],
    "unread_count": 0,
    "bvn": null,
    "has_bvn": false,
    "has_nin": false,
    "kyc_complete": false,
    "finger": false,
    "haspin": true
  }
}
```

---

## 6. Wallet Balance

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=wallet
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "balance": 30,
    "email": "softwareclone100@gmail.com"
  }
}
```

---

## 7. Wallet History

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=wallet_history
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "389",
        "trans_id": "131572313381357922",
        "email": "softwareclone100@gmail.com",
        "trans_amount": "200",
        "available_balance": "300",
        "wallet_status": "debit",
        "trans_date": "2026-05-19 13:09:00",
        "super_admin": "1",
        "status": "1"
      },
      {
        "id": "388",
        "trans_id": "267508545715242182",
        "email": "softwareclone100@gmail.com",
        "trans_amount": "400",
        "available_balance": "500",
        "wallet_status": "debit",
        "trans_date": "2026-05-19 13:06:46",
        "super_admin": "1",
        "status": "1"
      }
    ]
  }
}
```

> `wallet_status` values: `debit` | `credit` | `Refund`

---

## 8. Transactions

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=transactions
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "transactions": [
      {
        "id": "414",
        "title": "MTN SME 500MB",
        "phone": "08160327173",
        "date": "2026-05-19 13:50:07",
        "subtitle": "Successful",
        "amount": "270",
        "status": 1,
        "negative": true,
        "request_id": "plan_6a0ca2cf87529"
      },
      {
        "id": "413",
        "title": "MTN Cheap Data",
        "phone": "08160327173",
        "date": "2026-05-19 13:10:50",
        "subtitle": "Failed / Refunded",
        "amount": "445",
        "status": 0,
        "negative": false,
        "request_id": "173937125969784415"
      }
    ]
  }
}
```

> `status`: `1` = Successful, `0` = Failed/Refunded, `2` = Pending  
> `negative`: `true` = money was spent, `false` = money was returned

---

## 9. Dashboard Stats

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=dashboard_stats
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "wallet_balance": 30,
    "total_transactions": 28,
    "success_transactions": 9,
    "failed_transactions": 4,
    "notifications_count": 0,
    "referral_count": 0,
    "has_account": true,
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ]
  }
}
```

---

## 10. Submit KYC

> Submits BVN or NIN. Required before a virtual account can be generated.  
> Responds immediately — virtual account generation happens in background.

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=submit_kyc
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body (BVN)**
```json
{
  "bvn": "12345678901"
}
```

**Request Body (NIN)**
```json
{
  "nin": "12345678901"
}
```

**Request Body (Both)**
```json
{
  "bvn": "12345678901",
  "nin": "98765432101"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `bvn` | ❌ (one required) | 11-digit BVN |
| `nin` | ❌ (one required) | 11-digit NIN |

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "KYC submitted successfully",
    "needs_bvn": false,
    "account_ready": false,
    "setup_message": "Your virtual account is being generated, please check the KYC status in a moment.",
    "accounts": [],
    "acc_no": "",
    "bank_name": "",
    "acc_name": ""
  }
}
```

**Error Responses**
```json
{ "status": "error", "message": "BVN must be exactly 11 digits" }
{ "status": "error", "message": "This BVN is already linked to another account" }
{ "status": "error", "message": "BVN or NIN is required" }
```

> ⏳ After submitting KYC, poll `get_kyc_status` every 3–5 seconds until `account_ready` is `true`.

---

## 11. Get KYC Status

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=get_kyc_status
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "kyc_complete": false,
    "has_bvn": false,
    "has_nin": false,
    "has_account": true,
    "needs_bvn": true,
    "account_ready": true,
    "account_number": "6683940358",
    "bank_name": "Palmpay",
    "account_name": "Rahausub-Mah(Paymentpoint)",
    "acc_no": "6683940358",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ],
    "setup_message": "Submit your BVN or NIN to activate your virtual account."
  }
}
```

---

## 12. Funding Accounts

> Returns the user's virtual account numbers to fund their wallet.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=funding_accounts
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ],
    "has_accounts": true,
    "has_account": true,
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "account_number": "6683940358",
    "account_name": "Rahausub-Mah(Paymentpoint)",
    "provider": "PaymentPoint",
    "needs_bvn": false,
    "setup_message": ""
  }
}
```

> **Account not created yet response:**
```json
{
  "status": "success",
  "data": {
    "accounts": [],
    "has_account": false,
    "needs_bvn": true,
    "setup_message": "Please submit your BVN via the KYC section to activate your virtual account."
  }
}
```

---

## 13. Generate Virtual Account

> Manually triggers virtual account creation (requires KYC to be submitted first).

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=generate_account
Authorization: Bearer <token>
```

> Also works as `?action=generate_monnify` (backward compatible)

**Response (success)**
```json
{
  "status": "success",
  "data": {
    "message": "Virtual account generated successfully",
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Tunde Bello"
      },
      {
        "provider": "PaymentPoint",
        "bank_name": "Opay",
        "account_number": "8012345678",
        "account_name": "Tunde Bello"
      }
    ],
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Tunde Bello"
  }
}
```

**Error (KYC not submitted)**
```json
{
  "status": "error",
  "message": "Please submit your BVN or NIN via the KYC section first."
}
```

---

## 14. Verify Account

> Checks if a virtual account exists for the user.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=verify_account
Authorization: Bearer <token>
```

> Also works as `?action=verify_monnify`

**Live Response**
```json
{
  "status": "success",
  "data": {
    "has_account": true,
    "accounts": [
      {
        "provider": "PaymentPoint",
        "bank_name": "Palmpay",
        "account_number": "6683940358",
        "account_name": "Rahausub-Mah(Paymentpoint)"
      }
    ],
    "acc_no": "6683940358",
    "bank_name": "Palmpay",
    "acc_name": "Rahausub-Mah(Paymentpoint)",
    "account_number": "6683940358",
    "account_name": "Rahausub-Mah(Paymentpoint)"
  }
}
```

---

## 15. Buy Airtime

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=buy_airtime
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "amount": 200,
  "number": "08012345678",
  "network": "mtn",
  "pin": "1234"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `amount` | ✅ | Amount in Naira (integer) |
| `number` | ✅ | Phone number to recharge |
| `network` | ✅ | `mtn` / `airtel` / `glo` / `9mobile` / `etisalat` |
| `pin` | ✅ | Transaction PIN. Use `"fingerprint"` if fingerprint auth was used |

**Success Response**
```json
{
  "status": "success",
  "data": {
    "success": true,
    "message": "Airtime purchased successfully",
    "balance": 800
  }
}
```

**Error Responses**
```json
{ "status": "error", "message": "Insufficient balance" }
{ "status": "error", "message": "Invalid PIN" }
{ "status": "error", "message": "amount, number, network and pin are required" }
```

**Failed Transaction Response**
```json
{
  "status": "success",
  "data": {
    "success": false,
    "message": "Transaction failed, wallet refunded",
    "balance": 1000
  }
}
```

---

## 16. Buy Data (VTpass)

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=buy_data
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "amount": 1000,
  "number": "08012345678",
  "serviceID": "mtn-data",
  "variation": "mtn-1500mb-1000",
  "pin": "1234"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `amount` | ✅ | Amount in Naira |
| `number` | ✅ | Phone number to receive data |
| `serviceID` | ✅ | Network service ID (from `data_plans`) |
| `variation` | ✅ | Plan variation code (from `data_plans`) |
| `pin` | ✅ | Transaction PIN or `"fingerprint"` |

**Success Response**
```json
{
  "status": "success",
  "data": {
    "success": true,
    "message": "Data purchase successful",
    "balance": 0
  }
}
```

---

## 17. Data Plans

> Returns all data plans for a network from VTpass.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=data_plans&serviceID=mtn-data
```

> `serviceID` values: `mtn-data` | `glo-data` | `airtel-data` | `etisalat-data`  
> No auth required.

**Live Response (truncated)**
```json
{
  "status": "success",
  "data": {
    "plans": [
      {
        "plan_id": "mtn-10mb-100",
        "name": "110MB Daily Plan (1 Day) - N100",
        "amount": "100.00"
      },
      {
        "plan_id": "mtn-230mb-200",
        "name": "230MB Daily Plan (1 Day) - N200",
        "amount": "200.00"
      },
      {
        "plan_id": "mtn-1500mb-1000",
        "name": "1.5GB Weekly Plan (7 Days) - N1,000",
        "amount": "1000.00"
      },
      {
        "plan_id": "mtn-5.5gb-3500",
        "name": "7GB Monthly Plan - N3,500",
        "amount": "3500.00"
      }
    ]
  }
}
```

> Use `plan_id` as the `variation` in `buy_data`, and `serviceID` as the `serviceID`.

---

## 18. Data Types

> Returns available data plan categories for a network (SME, Corporate Gifting, etc.) — used with `other_data_plans`.

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=data_types&serviceID=mtn-data
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "types": [
      { "id": "1", "name": "MTN SME", "code": "mtnsme" },
      { "id": "2", "name": "MTN CORPORATE GIFTING", "code": "mtncg" },
      { "id": "7", "name": "MTN AWOOF", "code": "mtnawoof" },
      { "id": "9", "name": "DATA SHARE", "code": "mtnshare" },
      { "id": "10", "name": "DATA COUPONS", "code": "mtncoupons" },
      { "id": "11", "name": "MTN SME 2", "code": "mtnsme2" }
    ]
  }
}
```

---

## 19. Other Data Plans

> Returns custom data plans stored in the database for a specific data type (e.g. SME plans from alternative providers).

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=other_data_plans&plan_id=1
```

| Query Param | Required | Description |
|-------------|----------|-------------|
| `plan_id` | ✅ | The `id` from `data_types` response |

**Response**
```json
{
  "status": "success",
  "data": {
    "plans": [
      {
        "id": "42",
        "plan_id": "SME500",
        "api_id": "3",
        "name": "MTN SME 500MB (30 Days)",
        "validity": "30 Days",
        "amount": 270
      }
    ]
  }
}
```

---

## 20. Buy Other Data

> Purchases a data plan from `other_data_plans` (non-VTpass alternative providers).

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=buy_other_data
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "number": "08012345678",
  "plan_id": "42",
  "pin": "1234"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `number` | ✅ | Phone number to receive data |
| `plan_id` | ✅ | The `id` from `other_data_plans` response |
| `pin` | ✅ | Transaction PIN or `"fingerprint"` |

**Success Response**
```json
{
  "status": "success",
  "data": {
    "success": true,
    "message": "Data purchase successful",
    "balance": 730,
    "api_response": { "Status": "Successful", "id": 12345 }
  }
}
```

---

## 21. Get Notifications

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=notifications
Authorization: Bearer <token>
```

> Also works as `?action=get_notifications`

**Live Response**
```json
{
  "status": "success",
  "data": {
    "notifications": [
      {
        "id": 1,
        "title": "Wallet Credited",
        "message": "N500.00 has been added to your wallet by John Doe. New balance: N530.00.",
        "type": "success",
        "target": "specific",
        "target_email": "softwareclone100@gmail.com",
        "created_at": "2026-06-20 08:00:00",
        "is_read": false,
        "read": false
      }
    ],
    "unread_count": 1
  }
}
```

> `type` values: `info` | `success` | `warning` | `danger`  
> Empty notifications array returns: `{ "notifications": [], "unread_count": 0 }`

---

## 22. Get Unread Count

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=get_unread_count
Authorization: Bearer <token>
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "unread_count": 0
  }
}
```

---

## 23. Mark Notification Read

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=mark_notification_read
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "notification_id": 5
}
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "Marked as read"
  }
}
```

**Error**
```json
{ "status": "error", "message": "notification_id required" }
{ "status": "error", "message": "Notification not found" }
```

---

## 24. Mark All Notifications Read

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=mark_all_notifications_read
Authorization: Bearer <token>
Content-Type: application/json
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "All notifications marked as read"
  }
}
```

---

## 25. Referral Stats

**Request**
```
GET https://api.bikyensub.com.ng/api.php?action=referral
Authorization: Bearer <token>
```

> Also works as `?action=get_referral_stats`

**Live Response**
```json
{
  "status": "success",
  "data": {
    "referral_code": "e616a466e3267cb7996eebbc1f56ce30",
    "referral_link": "https://bikyensub.com.ng/easyfinder/dashboard/register?join_with_referal=e616a466e3267cb7996eebbc1f56ce30",
    "total_referred": 0,
    "total_earnings": 0,
    "referred_users": [],
    "share_message": "Join Bikyensub and earn on every data, airtime purchase! Use my referral code: e616a466e3267cb7996eebbc1f56ce30 — Sign up at https://bikyensub.com.ng/easyfinder/dashboard/register?join_with_referal=e616a466e3267cb7996eebbc1f56ce30"
  }
}
```

> When users are referred:
```json
{
  "referred_users": [
    {
      "sname": "Tunde",
      "oname": "Bello",
      "email": "tunde@example.com",
      "date_join": "2026-06-01 10:00:00"
    }
  ]
}
```

---

## 26. Check Fingerprint

> Checks if fingerprint/biometric login is enabled for a user (no auth token required).

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=check_fingerprint
Content-Type: application/json
```

**Request Body**
```json
{
  "email": "softwareclone100@gmail.com"
}
```

**Live Response**
```json
{
  "status": "success",
  "data": {
    "finger": false,
    "email": "softwareclone100@gmail.com"
  }
}
```

---

## 27. Toggle Fingerprint

> Enables or disables fingerprint login for the authenticated user.

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=toggle_fingerprint
Authorization: Bearer <token>
```

**Response (enabled)**
```json
{
  "status": "success",
  "data": {
    "finger": true,
    "message": "Fingerprint enabled"
  }
}
```

**Response (disabled)**
```json
{
  "status": "success",
  "data": {
    "finger": false,
    "message": "Fingerprint disabled"
  }
}
```

---

## 28. Set PIN

> Sets the transaction PIN for a user.

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=set_pin
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "pin": "1234"
}
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "PIN set successfully"
  }
}
```

**Error**
```json
{ "status": "error", "message": "PIN must be 4-6 digits" }
```

---

## 29. Change PIN

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=change_pin
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "old_pin": "1234",
  "new_pin": "5678"
}
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "PIN changed successfully"
  }
}
```

**Error**
```json
{ "status": "error", "message": "Current PIN is incorrect" }
```

---

## 30. Change Password

**Request**
```
POST https://api.bikyensub.com.ng/api.php?action=change_password
Authorization: Bearer <token>
Content-Type: application/json
```

**Request Body**
```json
{
  "old_password": "123456",
  "new_password": "NewSecure1"
}
```

**Response**
```json
{
  "status": "success",
  "data": {
    "message": "Password changed successfully"
  }
}
```

**Error**
```json
{ "status": "error", "message": "Current password is incorrect" }
```

---

## 31. Save Device Token (FCM)

> Call this immediately after login to register the device for push notifications.

**Request**
```
POST https://api.bikyensub.com.ng/saveDeviceToken.php
Content-Type: application/json
```

**Request Body**
```json
{
  "token": "f1d2361866eadefeb840e8bdef65c89ac52cab58d93fa0176ce86c5af3872e1a",
  "fcm_token": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]",
  "platform": "android"
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `token` | ✅ | User auth token (from login) |
| `fcm_token` | ✅ | Firebase/Expo push token from the device |
| `platform` | ❌ | `android` (default) or `ios` |

**Response**
```json
{
  "success": true,
  "message": "Device token saved"
}
```

**Error**
```json
{ "success": false, "message": "Unauthorized" }
{ "success": false, "message": "fcm_token is required" }
```

> 📲 After saving, wallet credit events will automatically trigger a push notification to the device.

---

## 32. Broadcast Notification (Admin)

> Sends a push notification to ALL registered devices. Admin only.

**Request**
```
POST https://api.bikyensub.com.ng/broadcastNotification.php
Content-Type: application/json
```

**Request Body**
```json
{
  "admin_key": "BikyenSubAdmin2026!",
  "title": "New Data Deal!",
  "body": "MTN 1GB is now N200 — grab it now!",
  "platform": "all",
  "data": {
    "screen": "DataPlans"
  }
}
```

| Field | Required | Description |
|-------|----------|-------------|
| `admin_key` | ✅ | `BikyenSubAdmin2026!` |
| `title` | ✅ | Notification title |
| `body` | ✅ | Notification body text |
| `platform` | ❌ | `all` (default) / `android` / `ios` |
| `data` | ❌ | Extra data payload (e.g. screen to navigate to) |

**Response**
```json
{
  "success": true,
  "message": "Sent: 150, Failed: 2",
  "total_devices": 152
}
```

---

## 33. Send Push to User (Admin)

> Sends a push notification to a specific user's device(s).

**Request**
```
POST https://api.bikyensub.com.ng/sendPushToUser.php
Content-Type: application/json
```

**Request Body (by email)**
```json
{
  "admin_key": "BikyenSubAdmin2026!",
  "email": "softwareclone100@gmail.com",
  "title": "Your order is ready",
  "body": "Your data purchase has been delivered.",
  "data": {
    "screen": "Transactions"
  }
}
```

**Request Body (by user_id)**
```json
{
  "admin_key": "BikyenSubAdmin2026!",
  "user_id": 439,
  "title": "Wallet Alert",
  "body": "Your wallet was credited with N500."
}
```

**Response**
```json
{
  "success": true,
  "message": "Sent: 1, Failed: 0"
}
```

---

## 34. PaymentPoint Webhook

> Called automatically by PaymentPoint when a user funds their wallet.  
> **You do NOT call this — PaymentPoint calls it automatically.**

**Webhook URL (set this in your PaymentPoint dashboard)**
```
https://api.bikyensub.com.ng/webhook.php
```

**What happens automatically when a payment arrives:**
1. ✅ Wallet balance is credited
2. ✅ Payment recorded in `payment_history_tbl`
3. ✅ In-app notification created ("Wallet Credited")
4. ✅ FCM push sent to user's device

**Sample PaymentPoint payload (for reference)**
```json
{
  "notification_status": "payment_successful",
  "transaction_status": "success",
  "transaction_id": "PP_20260620_ABC123",
  "amount_paid": 500,
  "settlement_amount": 498.5,
  "settlement_fee": 1.5,
  "receiver": {
    "account_number": "6683940358",
    "bank": "Palmpay",
    "name": "Rahausub-Mah"
  },
  "sender": {
    "name": "John Doe",
    "account_number": "0012345678",
    "bank": "GTBank"
  },
  "customer": {
    "email": "softwareclone100@gmail.com",
    "name": "Mahmud Muhammad"
  },
  "timestamp": "2026-06-20T08:00:00"
}
```

---

## Error Responses

All errors return:
```json
{
  "status": "error",
  "message": "Human readable error message"
}
```

| HTTP Code | Meaning |
|-----------|---------|
| `400` | Bad request — missing or invalid fields |
| `401` | Unauthorized — no token / invalid token |
| `404` | Not found |
| `405` | Method not allowed (POST required) |
| `409` | Conflict — e.g. BVN already used by another account |
| `422` | Unprocessable — e.g. KYC required before generating account |
| `503` | Service unavailable — database error |

---

## Response Wrapper

Every response from `api.php` follows this structure:

**Success:**
```json
{
  "status": "success",
  "data": { ... }
}
```

**Error:**
```json
{
  "status": "error",
  "message": "Description of what went wrong"
}
```

> `saveDeviceToken.php`, `broadcastNotification.php`, and `sendPushToUser.php` use a slightly different format:
```json
{ "success": true,  "message": "..." }
{ "success": false, "message": "..." }
```

---

## Quick Reference — All Actions

| Action | Method | Auth | Description |
|--------|--------|------|-------------|
| `health` / `ping` | GET | ❌ | API health check |
| `register` | POST | ❌ | Create account |
| `login` | POST | ❌ | Login & get token |
| `verify_token` | GET | ✅ | Validate token on app start |
| `profile` / `init` | GET | ✅ | Full user profile |
| `wallet` | GET | ✅ | Wallet balance |
| `wallet_history` | GET | ✅ | Wallet credit/debit history |
| `transactions` | GET | ✅ | Transaction history |
| `dashboard_stats` | GET | ✅ | Aggregated stats |
| `submit_kyc` | POST | ✅ | Submit BVN / NIN |
| `get_kyc_status` | GET | ✅ | Check KYC & account status |
| `funding_accounts` | GET | ✅ | Virtual account numbers |
| `generate_account` / `generate_monnify` | POST | ✅ | Create virtual account |
| `verify_account` / `verify_monnify` | GET | ✅ | Check account exists |
| `buy_airtime` | POST | ✅ | Buy airtime |
| `buy_data` | POST | ✅ | Buy data (VTpass) |
| `data_plans` | GET | ❌ | VTpass data plans |
| `data_types` | GET | ❌ | Data plan categories |
| `other_data_plans` | GET | ❌ | Alternative data plans |
| `buy_other_data` | POST | ✅ | Buy from alternative provider |
| `notifications` / `get_notifications` | GET | ✅ | In-app notifications |
| `get_unread_count` | GET | ✅ | Unread notification count |
| `mark_notification_read` | POST | ✅ | Mark one as read |
| `mark_all_notifications_read` | POST | ✅ | Mark all as read |
| `referral` / `get_referral_stats` | GET | ✅ | Referral code & earnings |
| `check_fingerprint` | POST | ❌ | Check biometric setting |
| `toggle_fingerprint` | POST | ✅ | Enable/disable fingerprint |
| `set_pin` | POST | ✅ | Set transaction PIN |
| `change_pin` | POST | ✅ | Change transaction PIN |
| `change_password` | POST | ✅ | Change account password |

---

## Admin Endpoints (Separate Files)

| Endpoint | Auth | Description |
|----------|------|-------------|
| `POST /saveDeviceToken.php` | User token | Register FCM push token |
| `POST /broadcastNotification.php` | Admin key | Push to all devices |
| `POST /sendPushToUser.php` | Admin key | Push to one user |

**Admin Key:** `BikyenSubAdmin2026!`

---

## Developer Notes

1. **Always call `saveDeviceToken.php` after login** — this is how the device registers for push notifications
2. **KYC flow:** `submit_kyc` → poll `get_kyc_status` every 3s until `account_ready: true` → show account to user
3. **PIN handling:** Pass `"fingerprint"` as the `pin` value when the user authenticated with biometrics
4. **Token refresh:** Tokens don't expire automatically but are replaced on each login
5. **Webhook:** Set `https://api.bikyensub.com.ng/webhook.php` as the webhook URL in your PaymentPoint dashboard

---

*Bikyensub APK API v2.0 — PaymentPoint + FCM + KYC + Referral*
