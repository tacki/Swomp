<?php

namespace Swomp\Filters;

interface CompressorInterface
{
    public function compress($buffer);
}