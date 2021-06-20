<?php

namespace OCA\WorkflowMediaConverter\Support;

use OCP\Files\Node;

class Util
{
    public static function getNextcloudFolderName($path)
    {
        //               👇
        // '', 'admin', 'files', 'path/to/file.mov'
        return explode('/', $path, 4)[2];
    }
}
