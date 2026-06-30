<?php

function verifyUserToken($conn, $incomingToken) {

    if (empty($incomingToken)) {
        return ["success" => false, "message" => "Token required"];
    }

    $safe = mysqli_real_escape_string($conn, $incomingToken);

    // 1. Fast path: plain token stored directly
    $q = mysqli_query($conn,
        "SELECT id, sname, oname, email, phone, pin, token
         FROM users_tbl
         WHERE token = '$safe' AND status = 1 LIMIT 1");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);
        return [
            "success" => true,
            "user" => [
                "id"    => $row['id'],
                "name"  => $row['sname'] . " " . $row['oname'],
                "email" => $row['email'],
                "phone" => $row['phone'],
                "pin"   => $row['pin']
            ]
        ];
    }

    // 2. Legacy bcrypt fallback (limited to 50 most recent users)
    $q2 = mysqli_query($conn,
        "SELECT id, sname, oname, email, phone, pin, token
         FROM users_tbl
         WHERE token LIKE '\$2y\$%' AND status = 1
         ORDER BY id DESC LIMIT 50");

    if ($q2) {
        while ($row = mysqli_fetch_assoc($q2)) {
            if (password_verify($incomingToken, $row['token'])) {
                // Upgrade: store plain token for future fast lookups
                $newToken = bin2hex(random_bytes(32));
                $newSafe  = mysqli_real_escape_string($conn, $newToken);
                mysqli_query($conn,
                    "UPDATE users_tbl SET token='$newSafe' WHERE id=" . intval($row['id']));
                return [
                    "success" => true,
                    "user" => [
                        "id"    => $row['id'],
                        "name"  => $row['sname'] . " " . $row['oname'],
                        "email" => $row['email'],
                        "phone" => $row['phone'],
                        "pin"   => $row['pin']
                    ]
                ];
            }
        }
    }

    return ["success" => false, "message" => "Invalid or expired token"];
}
