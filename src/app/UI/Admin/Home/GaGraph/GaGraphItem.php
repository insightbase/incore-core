<?php

namespace App\UI\Admin\Home\GaGraph;


class GaGraphItem
{
    public function __construct(
        public \DateTime $date,
        public int $count,
    )
    {
    }
}