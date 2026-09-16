<?php

declare(strict_types=1);

final class StockService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly InventoryRepository $inventory
    ) {
    }

    public function record(int $itemId, string $type, int $quantity, string $note = ''): void
    {
        if (!in_array($type, ['receive', 'issue'], true)) {
            throw new InvalidArgumentException('Unknown movement type.');
        }
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be positive.');
        }

        $this->pdo->beginTransaction();
        try {
            $item = $this->inventory->find($itemId);
            if (!$item) {
                throw new RuntimeException('Item not found.');
            }

            $newBalance = (int) $item['balance'] + ($type === 'receive' ? $quantity : -$quantity);
            if ($newBalance < 0) {
                throw new DomainException('在庫数を超えて出庫することはできません。');
            }

            $statement = $this->pdo->prepare(
                'INSERT INTO stock_movements (item_id, movement_type, quantity, note)
                 VALUES (:item_id, :movement_type, :quantity, :note)'
            );
            $statement->execute([
                'item_id' => $itemId,
                'movement_type' => $type,
                'quantity' => $quantity,
                'note' => trim($note),
            ]);
            $this->inventory->updateBalance($itemId, $newBalance);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}

