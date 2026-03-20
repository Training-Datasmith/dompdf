<?php

declare (strict_types=1);
namespace Dompdf\Css\Content;

final class Close_Quote extends Content_Part
{
    public function __toString(): string
    {
        return 'close-quote';
    }
}