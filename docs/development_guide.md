# 开发指南

## 1. 技术栈要求
- **PHP**: 8.2 (FPM)
- **Nginx**: Alpine 版本
- **Database**: MySQL 8.0 (Native Password 认证)
- **Frontend**: Tailwind CSS 3 (通过 CDN 引入)

## 2. 核心代码逻辑
### 后端 API (`www/api.php`)
主要的接口处理逻辑包括：
- `GET ?action=list`: 获取所有服务的 JSON 数据。
- `POST`: 接收 JSON Payload，例如 `{ "action": "toggle", "id": 1, "status": "running" }`。

### 数据库连接 (`www/db.php`)
使用 PDO 进行连接，通过环境变量获取数据库配置。设置了 5 秒的超时时间，以防在容器初始化期间由于 MySQL 未就绪导致的长时间挂起。

## 3. 环境变量配置
在 `docker-compose.yml` 中定义的关键变量：
- `DB_HOST`: 数据库容器名
- `DB_NAME`: 数据库名称
- `LOG_PATH`: 日志存储绝对路径

## 4. 开发工作流
1.  修改 `www/` 下的文件。
2.  由于目录已挂载，修改将立即生效。
3.  如果修改了 `init.sql`，需清空 `data/` 目录并重启容器以重新初始化。
