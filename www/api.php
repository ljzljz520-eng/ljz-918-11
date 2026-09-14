<?php
require 'db.php';

header('Content-Type: application/json');

function writeAppLog($message)
{
    $logFile = getenv('LOG_PATH') ?: '/var/log/app/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    // Suppress errors if log file not writable, use stderr as fallback
    if (!@file_put_contents($logFile, $logEntry, FILE_APPEND)) {
        file_put_contents('php://stderr', "AppLog: $message\n");
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
        $services = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $services]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $actionType = $input['action'] ?? '';

    if ($actionType === 'toggle') {
        $id = $input['id'];
        $targetStatus = $input['status']; // 'running' or 'stopped'

        // Validate
        if (!in_array($targetStatus, ['running', 'stopped'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }

        try {
            // Update Service Status
            $stmt = $pdo->prepare("UPDATE services SET status = ? WHERE id = ?");
            $stmt->execute([$targetStatus, $id]);

            // Get Name for logging
            $stmtName = $pdo->prepare("SELECT name FROM services WHERE id = ?");
            $stmtName->execute([$id]);
            $serviceName = $stmtName->fetchColumn();

            // Insert into DB System Logs
            $logAction = $targetStatus === 'running' ? 'START' : 'STOP';
            $msg = "User manually changed status of $serviceName to $targetStatus.";

            $logStmt = $pdo->prepare("INSERT INTO system_logs (service_id, action, message) VALUES (?, ?, ?)");
            $logStmt->execute([$id, $logAction, $msg]);

            // Write File Log
            writeAppLog("Service [$serviceName] ID:$id changed to $targetStatus");

            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
    exit;
}