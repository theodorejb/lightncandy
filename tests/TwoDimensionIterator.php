<?php

namespace DevTheorem\Handlebars\Test;

class TwoDimensionIterator implements \Iterator
{
    private int $position = 0;
    private int $x = 0;
    private int $y = 0;

    public function __construct(
        private readonly int $w,
        private readonly int $h
    ) {}

    function rewind(): void {
        $this->position = 0;
        $this->x = 0;
        $this->y = 0;
    }

    function current(): int|float
    {
        return $this->x * $this->y;
    }

    function key(): string
    {
        return $this->x . 'x' . $this->y;
    }

    function next(): void {
        ++$this->position;
        $this->x = $this->position % $this->w;
        $this->y = floor($this->position / $this->w);
    }

    function valid(): bool {
        return $this->position < $this->w * $this->h;
    }
}
