<?php

namespace App\Support;

use App\Models\Payday;
use Carbon\Carbon;

class PayPeriod
{
    public function __construct(
        public Carbon $start,
        public Carbon $end,
        public ?Payday $payday = null,
        public ?Carbon $nextPayday = null,
    ) {
    }

    public function days(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function label(): string
    {
        if ($this->start->isSameMonth($this->end)) {
            return $this->start->format('M j').'–'.$this->end->format('j');
        }

        return $this->start->format('M j').' – '.$this->end->format('M j');
    }

    public function labelWithYear(): string
    {
        return $this->label().', '.$this->end->format('Y');
    }
}
