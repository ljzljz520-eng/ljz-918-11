<?php
require __DIR__ . '/selfcheck.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * 写应用日志（约束：日志只能写进 LOG 目录，越界路径强制回退到 LOG/app.log）
 */
function writeAppLog($message)
{
    $root    = panel_root();
    $logDir  = $root . '/LOG';
    $logFile = getenv('LOG_PATH') ?: $logDir . '/app.log';

    if (!is_path_within($logFile, $logDir)) {
        $logFile = $logDir . '/app.log';
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry  = "[$timestamp] $message" . PHP_EOL;
    // 日志目录不可写时降级到 stderr，不影响主流程
    if (!@file_put_contents($logFile, $logEntry, FILE_APPEND)) {
        file_put_contents('php://stderr', "AppLog: $message\n");
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ---- 目录结构自检接口（不依赖数据库，自检失败时也必须可访问） ----
if ($method === 'GET' && $action === 'selfcheck') {
    $result = run_selfcheck();
    if (!$result['ok']) {
        http_response_code(503);
    }
    echo json_encode([
        'status' => $result['ok'] ? 'success' : 'error',
        'data'   => $result,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- 其余接口一律先过目录自检，失败即拒绝服务（HTTP 503） ----
$check = run_selfcheck();
if (!$check['ok']) {
    http_response_code(503);
    echo json_encode([
        'status'  => 'error',
        'message' => '目录结构自检未通过，请先按页面提示修复目录问题',
        'data'    => $check,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 自检通过后再连接数据库
require __DIR__ . '/db.php';

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
