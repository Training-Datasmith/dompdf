<?php

declare(strict_types=1);

namespace Dompdf\Css\Content;

final class NoOpenQuote extends ContentPart
{
    public function __toString(): string
    {
        return 'no-open-quote';
    }
}
