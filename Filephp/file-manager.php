<?php
$message = '';
$content = '';
$filename = '';
$folder = '';
$action = '';
$fullPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = trim($_POST['filename']);
    $folder = trim($_POST['folder']);
    $content = $_POST['content'];
    $action = $_POST['action'];

    $folderPath = rtrim($folder, '/');
    $fullPath = $folderPath ? "$folderPath/$filename" : $filename;

    if ($action === 'create') {
        if (!file_exists($fullPath)) {
            if (!file_exists($folderPath) && $folderPath !== '') {
                mkdir($folderPath, 0777, true);
            }
            if (file_put_contents($fullPath, $content) !== false) {
                $message = "✅ File '$fullPath' created successfully.";
            } else {
                $message = "❌ Error writing to file.";
            }
        } else {
            $message = "⚠️ File already exists. Use 'Update' to modify it.";
        }
    } elseif ($action === 'update') {
        if (file_exists($fullPath)) {
            if (file_put_contents($fullPath, $content) !== false) {
                $message = "🔁 File '$fullPath' updated.";
            } else {
                $message = "❌ Error updating the file.";
            }
        } else {
            $message = "❌ Cannot update. File does not exist.";
        }
    } elseif ($action === 'delete') {
        if (file_exists($fullPath)) {
            unlink($fullPath);
            $message = "🗑️ File '$fullPath' deleted.";
            $fullPath = '';
        } else {
            $message = "❌ File does not exist.";
        }
    } elseif ($action === 'view') {
        if (file_exists($fullPath)) {
            header("Location: $fullPath");
            exit;
        } else {
            $message = "❌ File does not exist to view.";
        }
    }
} elseif (!empty($_GET['load'])) {
    $filename = $_GET['load'];
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
    } else {
        $message = "❌ File does not exist.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP File Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        .container {
            max-width: 850px;
            margin: auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #0d6efd;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        textarea {
            font-family: monospace;
            min-height: 250px;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }
        .create { background-color: #28a745; }
        .update { background-color: #007bff; }
        .delete { background-color: #dc3545; }
        .view   { background-color: #6c757d; }
        .msg {
            background: #fff3cd;
            border-left: 5px solid #ffeeba;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 16px;
        }
        @media (max-width: 600px) {
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>🗂️ File Manager (Create / Update / Delete / View)</h2>

    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" id="fileForm">
        <label>Filename (with extension):</label>
        <input type="text" name="filename" value="<?= htmlspecialchars($filename) ?>" required>

        <label>Folder (relative, optional):</label>
        <input type="text" name="folder" value="<?= htmlspecialchars($folder) ?>">

        <label>File Content:</label>
        <textarea name="content"><?= htmlspecialchars($content) ?></textarea>

        <div class="btn-group">
            <button type="submit" name="action" value="create" class="btn create">📝 Create</button>
            <button type="submit" name="action" value="update" class="btn update">🔁 Update</button>
            <button type="submit" name="action" value="delete" class="btn delete">🗑️ Delete</button>
            <button type="submit" name="action" value="view" class="btn view">👁️ View</button>
        </div>
    </form>

    <?php if ($fullPath && file_exists($fullPath) && ($action === 'create' || $action === 'update')): ?>
        <div style="text-align: center; margin-top: 25px;">
            <a href="<?= htmlspecialchars($fullPath) ?>" target="_blank" style="background:#0d6efd; padding: 12px 20px; border-radius: 5px; text-decoration: none; color: white; display: inline-block;">
                🔍 Open Live Preview
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
