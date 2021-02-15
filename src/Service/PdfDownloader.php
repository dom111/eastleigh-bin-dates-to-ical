<?php

declare(strict_types=1);

namespace App\Service;

use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

class PdfDownloader
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    public function parse(string $file): Document
    {
        $content = file_get_contents($file);

        return $this->parser->parseContent($content);
    }
}