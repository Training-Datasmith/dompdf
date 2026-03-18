<?php

declare(strict_types=1);

namespace Dompdf\Css\Content;

abstract class ContentPart
{
    public function equals(self $other): bool
    {
        return $other instanceof static;
    }
}
