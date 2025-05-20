<?php

    function appendToAuditLog($userId, $clientIP, $action, $msg) {
        global $conn; // Ensure $conn is available
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
        }
        echo("Appending $msg to audit log");

        switch ($action) {
            case 'LimitExceeded':
                $level = 2;
                break;
            case 'APIFail':
                $level = 3;
                break;
            default:
                $level = 1;
                break;
        }

        if (!isset($userId)) {$userId = 0;}

        $ipId = 0;
        if (isset($clientIP) && !empty($clientIP)) {
            $sql = "SELECT id FROM ipTable WHERE userId = ? AND ip = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $userId, $clientIP);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $ipId = $row['id'] ?? 0;
            $stmt->close();
        }

        $sql = "INSERT INTO auditoria (userId, action, msg, level, ipId) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issii", $userId, $action, $msg, $level, $ipId);
        if ($stmt->execute()) {
            return ['success' => 'Action logged successfully'];
        } else {
            return ['error' => 'Failed to log action'];
        }
    }

?>