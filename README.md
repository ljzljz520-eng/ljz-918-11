# PHP Service Panel

## 🛠 技术栈
- Frontend: HTML5 + TailwindCSS (CDN)
- Backend: PHP 8.2 (FPM)
- Database: MySQL 8.0
- Server: Nginx (Alpine)

## 🚀 启动指南
1. 确保 Docker Desktop 已启动。
2. 在根目录执行：`docker compose up --build`
3. 等待容器启动完成...（首次启动 MySQL 初始化可能需要半分钟）

## 🔗 服务地址
- Web 管理面板: http://localhost:8080
- 数据库地址: localhost:3306 (内部服务名: db:3306)

## 🧪 测试账号
- 登录权限: 无需登录 (公开仪表盘)
- 数据库普通用户: user / userpassword
- 数据库 Root 账号: root / rootpassword

## 📁 项目文档
- [实施方案](docs/implementation_plan.md)
- [任务清单](docs/task_list.md)
- [演示说明](docs/walkthrough.md)
- [架构设计](docs/architecture.md)
- [开发指南](docs/development_guide.md)
- [测试报告](docs/testing_report.md)
- [用户手册](docs/user_manual.md)

## 📁 目录说明
- `www/`: PHP 源代码
- `data/`: 数据库文件持久化
- `LOG/`: 系统日志 (Nginx, App, MySQL)
- `config/`: 配置文件
- `docs/`: 详细中文项目文档

## 🩺 目录结构自检
页面打开时会先自检 `www`、`data`、`LOG` 三个目录是否存在且可写，并校验“数据库文件在 `data/` 下、日志只写 `LOG/`”的约束；自检失败时页面只显示修复建议，不展示业务按钮，接口返回 503。修复后点击页面上的“重新自检”即可恢复。
