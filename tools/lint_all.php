<?php
$root = __DIR__ . "/..";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$errors = [];
foreach ($it as $f) {
    if ($f->isFile() && preg_match('/\.php$/', $f->getFilename())) {
        $path = $f->getPathname();
        echo "-- $path --\n";
        $out = null;
        $ret = null;
        exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $ret);
        if ($ret !== 0) {
            echo implode("\n", $out) . "\n";
            $errors[$path] = $out;
        } else {
            echo "No syntax errors detected in $path\n";
        }
    }
}
if (!empty($errors)) {
    echo "\nSummary: Found syntax errors in " . count($errors) . " files.\n";
    exit(1);
}
echo "\nAll checked PHP files passed syntax check.\n";
