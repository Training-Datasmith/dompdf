<?php

declare (strict_types=1);
namespace Dompdf\Css\Content;

final class Attr extends Content_Part
{
    /**
     * @var string
     */
    public $attribute;
    public function __construct(string $attribute)
    {
        $this->attribute = $attribute;
    }
    public function equals(Content_Part $other): bool
    {
        return $other instanceof self && $other->attribute === $this->attribute;
    }
    public function __toString(): string
    {
        return "attr({$this->attribute})";
    }
}