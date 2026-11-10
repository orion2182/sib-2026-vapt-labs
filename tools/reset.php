<?php
declare(strict_types=1);

/**
 * Reset a built target without shell quoting problems.
 * Usage: php tools/reset.php <package-root>
 */

$root = $argv[1] ?? '';
if ($root === '' || !is_file($root . '/shared/harness/lib.php')) {
    fwrite(STDERR, "usage: php tools/reset.php <package-root>\n");
    exit(2);
}

require $root . '/shared/harness/lib.php';
lab_reset();
echo "reset-ok\n";
