<?php

namespace App\Shared\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class MenuExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function doSomething($value)
    {
        // ...
    }
}
