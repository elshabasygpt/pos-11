<?php

declare(strict_types=1);

namespace App\Application\Sales\DTOs;

final class InvoiceItemDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly float $quantity,
        public readonly float $unitPrice,
        public readonly float $discountPercent = 0,
        public readonly float $vatRate = 15, // Default 15% VAT
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            quantity: (float) $data['quantity'],
            unitPrice: (float) $data['unit_price'],
            discountPercent: (float) ($data['discount_percent'] ?? 0),
            vatRate: (float) ($data['vat_rate'] ?? 15),
        );
    }
}
