# Baseline audit

Audit target: upstream commit [`9c82d29b`](https://github.com/theamanjs/Store-Inventory-Management/tree/9c82d29b214ec3506e729c17c155281c4c518d88)

This is a source-code review and local modernization exercise. No third-party server was probed, and no real credentials or personal data were used.

## Confirmed source-level findings

| ID | Original location | Observation | Modernized handling |
|---|---|---|---|
| A-01 | [`index.php`](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/index.php) | Login input is concatenated into SQL. | PDO prepared statement with a bound email parameter. |
| A-02 | [`db-without-dummy-data.sql`](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/db/db-without-dummy-data.sql) | The seeded password is stored as plaintext. | `password_hash()` at setup and `password_verify()` at login. |
| A-03 | [`connection.php`](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/connection/connection.php) | Database settings are hard-coded in a tracked file. | Environment variables or an ignored local `config.php`. |
| A-04 | [`viewReceived.php`](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/viewReceived.php) | Delete is selected by a query-string value and executed through GET. | Authenticated POST-only endpoint with CSRF verification. |
| A-05 | Multiple PHP pages | Database values are concatenated into HTML without contextual escaping. | Central `e()` helper using `htmlspecialchars()`. |
| A-06 | [`viewReceived.php`](https://github.com/theamanjs/Store-Inventory-Management/blob/9c82d29b214ec3506e729c17c155281c4c518d88/viewReceived.php) | Entire `received` and `issued` tables are selected and rendered. | Server-side count, prefix search, `LIMIT` and `OFFSET`. |
| A-07 | Multiple PHP pages | Redirects after failed authentication do not terminate execution. | `redirect()` always calls `exit`. |

## Reproduction boundary

Security behavior is tested only against the fork in an isolated test database. This repository does not provide attack instructions for third-party systems. Findings describe the reviewed source and the corresponding defensive change.

