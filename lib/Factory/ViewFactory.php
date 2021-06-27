<?php

namespace OCA\WorkflowMediaConverter\Factory;

use OC\Files\View;

class ViewFactory
{
    public function create($root)
    {
        return new View($root);
    }
}
