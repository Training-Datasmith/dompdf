<?php

declare (strict_types=1);
namespace Dompdf\Css\Content;

final class String_Part extends Content_Part
{
    /**
     * @var string
     */
    public $string;
    public function __construct(string $string)
    {
        $this->string = $string;
    }
    public function equals(Content_Part $other): bool
    {
        return $other instanceof self && $other->string === $this->string;
    }
    public function __toString(): string
    {
        return '"' . $this->string . '"';
    }
}