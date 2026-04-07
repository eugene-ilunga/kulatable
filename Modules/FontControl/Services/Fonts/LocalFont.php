<?php

namespace Modules\FontControl\Services\Fonts;

use Endroid\QrCode\Label\Font\FontInterface;

class LocalFont implements FontInterface
{
    public function __construct(
        private string $path,
        private int $size = 16
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
