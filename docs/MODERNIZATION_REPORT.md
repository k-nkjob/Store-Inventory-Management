# Modernization report

## Objective

Demonstrate maintenance of an inherited codebase: understand the baseline, define reproducible checks, change one responsibility at a time, and preserve attribution.

## Architecture changes

- `Database`: PDO connection policy and driver selection.
- `Auth`: prepared login lookup, password verification, session regeneration and logout.
- `Csrf`: per-session token generation and constant-time verification.
- `Validator`: server-side item and stock-movement validation.
- `InventoryRepository`: SQL access, prefix search and pagination.
- `StockService`: transactional receive/issue operations and negative-stock prevention.
- `public`: request handling and escaped presentation only.

## Performance comparison contract

`modernized/tests/performance.php` creates the same 10,000-row SQLite fixture for both paths:

1. Baseline-shaped path: load every row and filter in PHP.
2. Modernized path: run prefix search in SQL and return at most 20 rows.

The script verifies result parity and reports elapsed time, rows loaded and SQLite's query plan. Elapsed time is informational because CI hardware varies. The README must only contain measured output from a completed run.

## Compatibility

- Development: PHP 8.3 + SQLite in Codespaces.
- Deployment: PHP 8.x + MySQL through PDO.
- Database-specific DDL is kept in separate files.

