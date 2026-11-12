<?php

declare(strict_types=1);

namespace App\DTOs\Readings;

use Carbon\CarbonImmutable;

final readonly class StoreReadingData
{
    public function __construct(
        public string $meterId,
        public int $zone,
        public int $value,
        public CarbonImmutable $date,
        public ?string $photo = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            meterId: $data['meter_id'],
            zone: $data['zone'],
            value: $data['value'],
            date: CarbonImmutable::parse($data['date'])->startOfDay(),
            photo: $data['photo'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
