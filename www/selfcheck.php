<?php
/**
 * 目录结构自检模块
 *
 * 规则：
 *  1. www / data / LOG 三个核心目录必须存在且可写；
 *  2. 数据库文件必须放在 data/ 目录下；
 *  3. 日志文件只能写进 LOG/ 目录。
 *
 * 本文件仅提供函数，供 api.php 引用，不直接产生输出。
 */

/** 项目根目录：优先 PANEL_ROOT 环境变量，否则取 www 的上一级目录 */
function panel_root(): string
{
    $root = getenv('PANEL_ROOT') ?: dirname(__DIR__);
    return rtrim(str_replace('\\', '/', $root), '/');
}

/** 规范化路径（不依赖路径真实存在），用于目录包含关系判断 */
function normalize_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $prefix = str_starts_with($path, '/') ? '/' : '';
    $segments = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $seg;
    }
    return $prefix . implode('/', $segments);
}

/** 判断 $path 是否位于 $base 目录之内（含 $base 本身） */
function is_path_within(string $path, string $base): bool
{
    $realBase = realpath($base);
    $baseNorm = normalize_path($realBase !== false ? $realBase : $base);
    $realPath = realpath($path);
    $pathNorm = normalize_path($realPath !== false ? $realPath : $path);
    return $pathNorm === $baseNorm || str_starts_with($pathNorm, $baseNorm . '/');
}

/** 检查单个核心目录的存在性与可写性，并给出修复建议 */
function check_directory(string $root, string $name): array
{
    $path = $root . '/' . $name;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);

    $suggestion = null;
    if (!$exists) {
        $suggestion = "mkdir -p $path && chmod 775 $path";
    } elseif (!$writable) {
        $suggestion = "chmod 775 $path   # 或 sudo chown -R <Web运行用户> $path";
    }

    return [
        'name'       => $name,
        'path'       => $path,
        'exists'     => $exists,
        'writable'   => $writable,
        'ok'         => $exists && $writable,
        'suggestion' => $suggestion,
    ];
}

/** 路径约束检查：数据库必须在 data/ 下，日志只能在 LOG/ 下 */
function check_path_constraints(string $root): array
{
    $dbPath  = getenv('DB_DATA_PATH') ?: $root . '/data';
    $logPath = getenv('LOG_PATH')     ?: $root . '/LOG/app.log';

    return [
        [
            'key'        => 'db_under_data',
            'label'      => '数据库文件必须放在 data/ 目录下',
            'path'       => $dbPath,
            'ok'         => is_path_within($dbPath, $root . '/data'),
            'suggestion' => '将环境变量 DB_DATA_PATH 调整为 ' . $root . '/data 之内的路径',
        ],
        [
            'key'        => 'log_under_log_dir',
            'label'      => '日志文件只能写进 LOG/ 目录',
            'path'       => $logPath,
            'ok'         => is_path_within($logPath, $root . '/LOG'),
            'suggestion' => '将环境变量 LOG_PATH 调整为 ' . $root . '/LOG 之内的路径',
        ],
    ];
}

/** 执行完整自检，返回结构化结果（ok / root / dirs / constraints） */
function run_selfcheck(): array
{
    $root = panel_root();

    $dirs = [
        check_directory($root, 'www'),
        check_directory($root, 'data'),
        check_directory($root, 'LOG'),
    ];
    $constraints = check_path_constraints($root);

    $ok = true;
    foreach (array_merge($dirs, $constraints) as $item) {
        if (empty($item['ok'])) {
            $ok = false;
            break;
        }
    }

    return [
        'ok'          => $ok,
        'root'        => $root,
        'dirs'        => $dirs,
        'constraints' => $constraints,
    ];
}
