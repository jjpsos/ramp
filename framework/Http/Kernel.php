<?php // framework/Http/Kernel.php

namespace JimSos\Framework\Http;

class Kernel
{
    public function handle(Request $request): Response
    {
        $content = '<h1>RAMP KERNEL</h1>';

        return new Response($content);
    }
}