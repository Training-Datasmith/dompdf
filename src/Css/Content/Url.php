<?php

declare (strict_types=1);
namespace Dompdf\Css\Content;

final class Url extends Content_Part
{
    /**
     * @var string
     */
    public $url;
    public function __construct(string $url)
    {
        $this->url = $url;
    }
    public function equals(Content_Part $other): bool
    {
        return $other instanceof self && $other->url === $this->url;
    }
    public function __toString(): string
    {
        return 'url("' . str_replace('"', '\"', $this->url) . '")';
    }
}