# 贡献指南

感谢你改进 Liuren。请保持变更聚焦、可测试、易审查。

## 本地初始化

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
npm run build
```

## 提交 Pull Request 前

请运行：

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

如果改动涉及起盘创建或计算逻辑，还需要运行专项回归测试：

```bash
php artisan test tests/Feature/PanResourceTest.php
```

## Pull Request 要求

- 说明行为变化以及为什么需要这个变化。
- 对用户可见行为或数据结构变更补充测试。
- 除非是明确需要发布的静态资源，不要把生成文件混入 diff。
- 不要提交 `.env`、SQLite 数据库、API key、凭据、日志、生产导出或真实用户数据。

## 起盘计算变更

起盘创建逻辑由 golden fixture 保护。如果计算结果变化是有意的，请在同一个 PR 中更新 fixture，并说明新结果为什么正确。
