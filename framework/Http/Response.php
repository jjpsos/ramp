<?php // framework/Http/Response.php

namespace JimSos\Framework\Http;

class Response
{
    public function __construct(
        private ?string $content = '',
        private int $status = 200,
        private array $headers = []
    )
    {
    }

    public function send(): void
    {
        echo $this->content;
    }
}