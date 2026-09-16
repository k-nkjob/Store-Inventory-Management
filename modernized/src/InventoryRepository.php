<?php

declare(strict_types=1);

final class InventoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function paginate(string $query, int $page, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $prefix = $this->escapeLike(trim($query)) . '%';

        if ($query === '') {
            $countStatement = $this->pdo->query('SELECT COUNT(*) FROM items');
            $statement = $this->pdo->prepare(
                'SELECT id, name, category, unit, balance, created_at
                 FROM items ORDER BY id DESC LIMIT :limit OFFSET :offset'
            );
        } else {
            $countStatement = $this->pdo->prepare(
                "SELECT COUNT(*) FROM items
                 WHERE name LIKE :prefix ESCAPE '!'
                    OR category LIKE :prefix ESCAPE '!'"
            );
            $countStatement->execute(['prefix' => $prefix]);
            $statement = $this->pdo->prepare(
                "SELECT id, name, category, unit, balance, created_at
                 FROM items
                 WHERE name LIKE :prefix ESCAPE '!'
                    OR category LIKE :prefix ESCAPE '!'
                 ORDER BY id DESC LIMIT :limit OFFSET :offset"
            );
            $statement->bindValue(':prefix', $prefix);
        }

        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();
        $total = (int) $countStatement->fetchColumn();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function allForSelect(): array
    {
        return $this->pdo->query(
            'SELECT id, name, unit, balance FROM items ORDER BY name ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, category, unit, balance FROM items WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();
        return $item ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO items (name, category, unit, balance)
             VALUES (:name, :category, :unit, 0)'
        );
        $statement->execute([
            'name' => trim((string) $data['name']),
            'category' => trim((string) $data['category']),
            'unit' => trim((string) $data['unit']),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM items WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->rowCount() === 1;
    }

    public function updateBalance(int $id, int $balance): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE items SET balance = :balance, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute(['balance' => $balance, 'id' => $id]);
    }

    public function recentMovements(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->pdo->prepare(
            'SELECT m.id, m.movement_type, m.quantity, m.note, m.created_at,
                    i.name AS item_name, i.unit
             FROM stock_movements m
             INNER JOIN items i ON i.id = m.item_id
             ORDER BY m.id DESC LIMIT :limit'
        );
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
