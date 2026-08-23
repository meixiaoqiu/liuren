# Liuren

Liuren 是一个基于 Laravel、Filament 和 Livewire 的大六壬起盘应用。项目包含 Filament 后台登录与起盘资源，以及起盘结果回归测试。

本仓库不包含私有运行数据、生产密钥或打包数据库。

## 功能

- 通过 Filament 创建和保存大六壬盘记录。
- 使用 golden fixture 保护起盘创建结果，避免升级或重构导致数据漂移。
- `tools/chrome` 下提供本地开发用 Chrome 辅助工具。

## 环境要求

- PHP 8.3 或更高版本
- Composer 2
- Node.js 22.12 或更高版本
- 本地开发可使用 SQLite，也可使用 Laravel 支持的其他数据库

## 安装

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

如果保持 `.env.example` 默认的 SQLite 配置，本地开发时需要先创建数据库文件：

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

默认 seeder 只会在 `local` 和 `testing` 环境中创建演示用户。

### Docker 本地环境

项目已在以下容器拓扑中验证：

- Nginx 将 `liuren.local` 的站点根目录指向 `/var/www/html/liuren/public`，并把 PHP 请求转发到 `php85-fpm:9000`。
- PHP 8.5 FPM、Nginx 和 MySQL 使用同一个外部 Docker 网络 `dev-net`。
- MySQL 在应用 `.env` 中使用容器主机名 `mysql97`，不要使用 `127.0.0.1`。
- Windows hosts 文件包含 `127.0.0.1 liuren.local`。

应用 `.env` 的数据库部分可按本地凭据配置：

```dotenv
APP_URL=http://liuren.local
DB_CONNECTION=mysql
DB_HOST=mysql97
DB_PORT=3306
DB_DATABASE=dev_liuren
DB_USERNAME=dev_liuren
DB_PASSWORD=<local-only-password>
```

启动容器后，在 PHP 容器中完成初始化：

```bash
docker exec php85-fpm sh -lc "cd /var/www/html/liuren && composer install"
docker exec php85-fpm sh -lc "cd /var/www/html/liuren && php artisan migrate"
docker exec php85-fpm sh -lc "cd /var/www/html/liuren && php artisan test"
```

数据库密码只保存在被 Git 忽略的 `.env` 和本机 MySQL 配置中，不要写入 Compose 文件或提交到仓库。

## 配置

重要环境变量：

- `APP_NAME`：应用显示名称。
- `APP_URL`：应用访问地址。
- `APP_TIMEZONE`：默认时区。
- `APP_ADMIN_EMAILS`：可选，生产环境后台管理员邮箱白名单，多个邮箱用英文逗号分隔。

生产环境建议显式设置 `APP_ADMIN_EMAILS`。如果该值为空，后台访问只会在 `local` 和 `testing` 环境中默认放行，避免公开注册用户直接进入 Filament 后台。

## 生产部署

生产环境不要直接复用 `.env.example`。至少应确认：

- `APP_ENV=production`。
- `APP_DEBUG=false`。
- `APP_KEY` 由部署环境独立生成，且不进入 Git。
- `APP_URL` 使用真实 HTTPS 地址。
- `APP_ADMIN_EMAILS` 只包含受信任且已验证的管理员邮箱。
- `SESSION_SECURE_COOKIE=true`、`SESSION_HTTP_ONLY=true`、`SESSION_SAME_SITE=lax`。
- 数据库、邮件和外部服务密钥只通过部署平台的 secrets 或环境变量注入。

## 开发

```bash
composer run dev
```

也可以分别启动：

```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

## 测试

```bash
php artisan test
php artisan test tests/Feature/PanResourceTest.php
vendor/bin/pint --test
npm run build
npm audit --audit-level=moderate
composer audit --locked
```

`tests/Feature/PanResourceTest.php` 是起盘创建逻辑的核心回归测试。它会验证计算后的数据库记录是否与 `tests/Fixtures/pan_creation_hashes.json` 中的 golden fixture 一致。

## 数据与密钥

- `.env` 已被忽略，不应提交。
- `database/*.sqlite*` 已被忽略，不应提交。
- 不要提交生产导出、API key、日志、备份、真实用户数据或生成的私有数据。
- 公开示例只能写在 `.env.example` 中，并使用空值或占位说明。

## 许可证

Liuren 使用 MIT 许可证开源。正式授权文本见 `LICENSE`。
