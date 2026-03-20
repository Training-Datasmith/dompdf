<?php

declare (strict_types=1);
namespace Dompdf\Css\Content;

abstract class Content_Part
{
    public function equals(self $other): bool
    {
        return $other instanceof static;
    }
}