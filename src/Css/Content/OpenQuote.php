<?php

declare(strict_types=1);

namespace Dompdf\Css\Content;

final class OpenQuote extends ContentPart
{
    public function __toString(): string
    {
        return 'open-quote';
    }
}
