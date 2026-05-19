<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

enum Tier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold   = 'gold';

    public function rank(): int
    {
        return match($this) {
            self::Bronze => 1,
            self::Silver => 2,
            self::Gold   => 3,
        };
    }

    public function atLeast(?self $floor): bool
    {
        if ($floor === null) {
            return true;
        }

        return $this->rank() >= $floor->rank();
    }
}
