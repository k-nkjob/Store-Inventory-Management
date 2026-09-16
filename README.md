# Legacy PHP Inventory System Modernization

既存のPHP在庫管理OSSを対象に、コード調査、不具合修正、セキュリティ改善、責務分離、検索・一覧処理の改善を行う自主制作ポートフォリオです。

> This is an independent modernization study of an existing MIT-licensed project. It is not client work and is not presented as an official upstream release.

## Origin and license

- Upstream: [theamanjs/Store-Inventory-Management](https://github.com/theamanjs/Store-Inventory-Management)
- Audited baseline: [`9c82d29b`](https://github.com/theamanjs/Store-Inventory-Management/tree/9c82d29b214ec3506e729c17c155281c4c518d88)
- Original author: Amanjot Singh
- License: MIT

The original copyright notice and MIT license are retained in [`LICENSE`](LICENSE). The upstream source remains in this fork's history. Modernized code is isolated under [`modernized/`](modernized/).

## Purpose

新規開発ではなく、第三者が作成した既存コードを次の手順で保守できることを示す作品です。

1. 原版とライセンスを確認する
2. コードから問題を特定する
3. 再現条件と影響を文書化する
4. テストを追加する
5. 責務を分離しながら修正する
6. 同じデータで修正前後を比較する

## Confirmed baseline findings

原版の実コードから確認できた内容です。詳細と根拠リンクは [`docs/BASELINE_AUDIT.md`](docs/BASELINE_AUDIT.md) に記録しています。

- ログインSQLへの入力値直接結合
- 平文パスワードの保存と照合
- DB接続情報のハードコード
- GETリクエストによる削除
- CSRF対策の欠如
- DB取得値の未エスケープ出力
- 認証リダイレクト後も処理を継続できる構造
- 全件取得後に画面へ展開する一覧処理

No third-party server was tested. Review and verification are limited to this fork and isolated test databases.

## Modernized implementation

- PHP 8.3 and `strict_types`
- PDO and native prepared statements
- SQLite for Codespaces, MySQL DDL for deployment
- `password_hash()` / `password_verify()`
- Session ID regeneration and cookie controls
- CSRF tokens on state-changing requests
- POST-only delete and logout
- Central HTML escaping
- Server-side validation
- Transactional stock receive/issue processing
- Negative-stock prevention with rollback
- Prefix search, indexes, `LIMIT` / `OFFSET` pagination
- Security response headers
- GitHub Actions lint, smoke test and performance comparison

## Responsibility boundaries

| Component | Responsibility |
|---|---|
| `Database` | PDO connection and driver configuration |
| `Auth` | Login, session identity and logout |
| `Csrf` | Token generation and verification |
| `Validator` | Server-side input rules |
| `InventoryRepository` | SQL queries and persistence |
| `StockService` | Transactional inventory business rules |
| `public/` | HTTP request handling and escaped views |

## Run in Codespaces

1. Create a Codespace from the `modernization` branch.
2. Wait for the container setup and SQLite seed to finish.
3. Open forwarded port `8000`.
4. Use the development credentials printed by `database/seed.php`.

The default development credentials are intentionally limited to the local SQLite environment. Set `APP_ADMIN_EMAIL` and `APP_ADMIN_PASSWORD` before seeding any deployed environment.

Manual startup:

```bash
cd modernized
php database/seed.php
php -S 0.0.0.0:8000 -t public
```

## Automated verification

```bash
find modernized -name '*.php' -print0 | xargs -0 -n1 php -l
php modernized/tests/smoke.php
php modernized/tests/performance.php
```

The performance script builds a 10,000-row fixture and compares:

- baseline-shaped full-table fetch plus PHP filtering;
- SQL prefix search with a maximum 20-row page.

It verifies result parity and reports rows loaded, elapsed time and the query plan. Timing results are not claimed until a completed GitHub Actions run provides them.

### Recorded CI result

[GitHub Actions run #1](https://github.com/k-nkjob/Store-Inventory-Management/actions/runs/35047662647) completed successfully on Ubuntu 24.04 with PHP 8.3.33 and SQLite.

| Measurement | Baseline-shaped path | Modernized path |
|---|---:|---:|
| Dataset | 10,000 rows | 10,000 rows |
| Matching results | 100 rows | 100 rows |
| Rows loaded into PHP | 10,000 | 20 |
| Elapsed time in this run | 7.184 ms | 0.248 ms |

SQLite reported `SEARCH items USING COVERING INDEX idx_items_name`. The elapsed values are one reproducible CI observation, not a guarantee for every machine or production database. The durable improvement is the reduction from full-table application loading to indexed search with a bounded page.

## Documentation

- [Baseline audit](docs/BASELINE_AUDIT.md)
- [Modernization and benchmark design](docs/MODERNIZATION_REPORT.md)
- [Original upstream README](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/README.md)

## English summary

This portfolio project modernizes a third-party MIT-licensed PHP inventory application. It preserves attribution and history, documents source-level findings, and implements a PHP 8.3/PDO version with secure authentication, CSRF protection, transactional stock changes, server-side pagination, automated tests and a reproducible performance comparison.
