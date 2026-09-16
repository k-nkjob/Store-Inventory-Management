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

## Problem → Solution → Result

この作品では、単に新しい画面を作るのではなく、既存システムを読んで「何が問題か」「なぜ修正するのか」「修正後にどう変わったか」が分かる形で改修しています。

### 1. Security and maintainability

**Problem**

原版では、ログインSQLへの入力値直接結合、平文パスワード、GET削除、CSRF対策の欠如、DB取得値の未エスケープ出力、DB設定のハードコードなどを確認しました。また、PHP、SQL、HTML、CSS、JavaScriptの責務が近い場所に混在しており、機能追加時の影響範囲を追いにくい構造でした。

**Solution**

- PDO / Prepared Statementへ変更
- `password_hash()` / `password_verify()`による認証へ変更
- DB設定をアプリ本体から分離
- CSRFトークンを追加
- 削除・ログアウトをPOST処理へ変更
- HTML出力時のエスケープを共通化
- サーバー側バリデーションを追加
- `Database` / `Auth` / `Csrf` / `Validator` / `InventoryRepository` / `StockService`へ責務分離

**Result**

セキュリティ上の問題を修正すると同時に、認証、DBアクセス、入力検証、在庫処理の責務を分け、既存機能へ変更を加える際に影響範囲を追いやすい構成へ整理しました。

### 2. Inventory consistency

**Problem**

在庫の入庫・出庫は複数のDB更新を伴うため、途中で処理に失敗した場合に在庫数と履歴が不整合になる可能性があります。また、現在庫を超える出庫を許可すると負在庫が発生します。

**Solution**

入出庫処理をトランザクション化し、在庫数を超える出庫はサーバー側で拒否します。処理途中で異常が発生した場合はロールバックします。

**Result**

在庫数と入出庫履歴を一連の処理として扱い、在庫不足時の拒否とロールバックを自動テストで確認できる構成にしました。

### 3. Search and list performance

**Problem**

原版型の一覧処理では、DBから全件をPHPへ読み込んだ後にアプリケーション側で処理するため、データ件数が増えるほど不要な読み込みが増えます。

**Solution**

- 検索をDB側の前方一致検索へ変更
- DBインデックスを追加
- `LIMIT` / `OFFSET`によるページネーションを追加
- 1ページの取得件数を最大20件に制限

**Result**

GitHub Actions上で10,000件のテストデータを使って比較した結果、同じ100件の検索結果に対して、PHPへ読み込む件数は10,000件から20件へ減少しました。

| 項目 | 原版型 | 改修版 |
|---|---:|---:|
| 検索対象データ | 10,000件 | 10,000件 |
| 検索該当件数 | 100件 | 100件 |
| PHPへ読み込む件数 | 10,000件 | 20件 |
| 今回の実測時間 | 7.184 ms | 0.248 ms |

実測時間はGitHub Actions上での1回の観測値であり、すべての環境で同じ性能を保証するものではありません。恒久的な改善点は、全件をPHPへ読み込む方式から、インデックス付きSQL検索とページネーションへ変更したことです。

## Additional proposal-driven improvements

不具合やセキュリティ上の問題を修正した後、実際の操作フローも確認し、仕様として与えられていない部分についても「利用時に不便ではないか」という観点から追加改善を行いました。

### Per-item stock movement history

**気付いた不便**

既存画面では最近の入出庫は確認できますが、特定の商品について「いつ、何個入庫・出庫したのか」「そのときの備考は何だったのか」を商品単位で追う画面がありませんでした。

在庫管理では、現在庫の数字だけでなく、その数字になった経緯を確認できる方が実用的だと考えました。

**追加した機能**

商品一覧の商品名または「履歴」から、商品別の入出庫履歴画面へ移動できるようにしました。

表示内容:

- 商品名
- カテゴリ
- 現在庫
- 入庫 / 出庫
- 数量
- 処理日時
- 備考
- 履歴件数

履歴件数が増えた場合に備えてページネーションも実装しています。履歴が0件の場合も専用メッセージを表示します。

### Preserve the selected item when registering a movement

商品別履歴を追加した後、さらに操作してみると、履歴を見ている商品についてそのまま入出庫を登録したい場合に、登録画面でもう一度商品を選び直すのは不要な操作だと感じました。

そこで、商品別履歴画面の「＋ 入出庫登録」から遷移した場合は、その商品を登録画面で最初から選択済みにするよう改善しました。通常の入出庫登録画面を直接開いた場合は、従来どおり商品未選択の状態です。

これにより、

`商品を確認 → 履歴を見る → 同じ商品の入出庫を登録`

という操作を自然につなげられるようにしました。

この追加改善は、指定された不具合だけを直すのではなく、既存システムを実際に使いながら業務上の不便を見つけ、影響を限定した機能改善として提案・実装できることを示すためのものです。

## Browser verification

The modernized application was started in GitHub Codespaces with PHP 8.3 and SQLite. Login, prefix search, item registration, receive, issue, over-issue rejection, protected deletion, logout and persisted state after re-login were manually verified on 2026-09-16.

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
- Per-item movement ledger with dates, quantities, notes and pagination
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

1. Create a Codespace from the `master` branch.
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
- [InfinityFree deployment guide](modernized/DEPLOY_INFINITYFREE.md)
- [Original upstream README](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/README.md)

## English summary

This portfolio project modernizes a third-party MIT-licensed PHP inventory application. It preserves attribution and history, documents source-level findings, and implements a PHP 8.3/PDO version with secure authentication, CSRF protection, transactional stock changes, server-side pagination, automated tests and a reproducible performance comparison. In addition to source-level fixes, the project includes proposal-driven UX improvements such as per-item movement history and preserving item context when moving from history to stock registration.
