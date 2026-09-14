# 实施方案 - PHP 服务管理面板

## 目标描述
开发一个基于 PHP 的服务管理面板，用于监控和控制系统服务（模拟）。项目将使用 Docker 进行容器化，并遵循特定的目录结构：`www` 存放代码，`data` 存放数据库文件，`LOG` 存放日志。UI 将采用现代化的 TailwindCSS，并支持中文。

## 注意事项
> [!NOTE]
> “服务”将是数据库中的模拟条目。点击“开启/停止”将更新数据库状态并写入日志。真实的系统服务控制超出了标准 Web 容器的权限范围。

## 方案细节

### 目录结构
```
/php-service-panel
├── docker-compose.yml
├── Dockerfile (PHP 扩展)
├── config
│   └── nginx.conf
├── www
│   ├── index.php (前端 UI)
│   ├── api.php (后端接口)
│   └── db.php (数据库连接)
├── data (映射 MySQL 数据)
└── LOG (映射系统日志)
```

### Docker 配置
*   **php**: 基于 `php:8.2-fpm`，自定义 Dockerfile 安装 `pdo_mysql`。
*   **web**: 使用 `nginx:alpine`，配置反向代理处理 PHP 文件。
*   **db**: `mysql:8.0`，配置数据持久化和初始化脚本。

### 数据库设计
*   `services` 表：存储服务名称、描述、状态及更新时间。
*   `system_logs` 表：记录用户的操作审计日志。

### 应用逻辑
*   **api.php**: 提供服务列表查询和状态切换接口。
*   **index.php**: 单页面应用，使用原生 JS + TailwindCSS 实现动态交互。

### 日志规范
*   业务审计日志同步写入数据库和 `./LOG/app.log`。
*   Nginx 访问与错误日志映射到 `./LOG/nginx`。
