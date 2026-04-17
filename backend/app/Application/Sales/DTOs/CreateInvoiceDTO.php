<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

final class CreateInvoiceDTO
{
    public function __construct(
        public readonly ?string $customerId,
        public readonly string $type, // cash, credit
        public readonly string $warehouseId,
        public readonly array $items, // array of InvoiceItemDTO
        public readonly ?string $notes = null,
        public readonly float $discountPercent = 0,
    ) {}

    public static function fromRequest(array $data): self
    {
        $items = array_map(
            fn(array $item) => InvoiceItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        return new self(
            customerId: $data['customer_id'] ?? null,
            type: $data['type'],
            warehouseId: $data['warehouse_id'],
            items: $items,
            notes: $data['notes'] ?? null,
            discountPercent: (float) ($data['discount_percent'] ?? 0),
        );
    }
}
