<?php
// 设置 CDN 目录的基础路径
$cdn_base_path = 'cdn/';
// 设置用于 URL 的 CDN 基础路径
$cdn_url_base = '/' . $cdn_base_path;

// 递归函数来获取目录结构
function getDirContents($dir, $base = '') {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        $relativePath = $base . '/' . $file;
        
        if (is_dir($path)) {
            $result = array_merge($result, getDirContents($path, $relativePath));
        } else {
            $result[] = [
                'type' => 'file',
                'name' => $file,
                'path' => $relativePath,
                'size' => filesize($path),
                'modified' => filemtime($path)
            ];
        }
    }
    
    return $result;
}

// 获取文件列表
$files = getDirContents($cdn_base_path);

// 排序函数
function sortFiles($a, $b) {
    return strcmp($a['path'], $b['path']);
}

// 对文件列表进行排序
usort($files, 'sortFiles');

// 获取文件类型的 emoji
function getFileEmoji($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'js':
            return '📜'; // JavaScript
        case 'css':
            return '🎨'; // CSS
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'svg':
            return '🖼️'; // 图片
        default:
            return '📄'; // 其他文件
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CDN 文件浏览器</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            line-height: 1.6; 
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #333;
            text-align: center;
        }
        #search {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #ddd;
        }
        a {
            color: #1a0dab;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #008CBA;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #007B9A;
        }
    </style>
</head>
<body>
    <h1>CDN 文件浏览器</h1>
    <input type="text" id="search" placeholder="搜索文件...">
    <table id="fileTable">
        <thead>
            <tr>
                <th>文件名</th>
                <th>路径</th>
                <th>大小</th>
                <th>修改时间</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
            <tr>
                <td>
                    <?php echo getFileEmoji($file['name']); ?>
                    <a href="<?php echo $cdn_url_base . $file['path']; ?>" target="_blank"><?php echo htmlspecialchars($file['name']); ?></a>
                </td>
                <td><?php echo htmlspecialchars($file['path']); ?></td>
                <td><?php echo number_format($file['size'] / 1024, 2) . ' KB'; ?></td>
                <td><?php echo date('Y-m-d H:i:s', $file['modified']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="text-align: center; margin-top: 20px;">
        <a href="download_form.html" class="button">返回下载页面</a>
    </p>

    <script>
    document.getElementById('search').addEventListener('input', function() {
        var searchValue = this.value.toLowerCase();
        var tableRows = document.querySelectorAll('#fileTable tbody tr');
        
        tableRows.forEach(function(row) {
            var fileName = row.querySelector('td:first-child').textContent.toLowerCase();
            var filePath = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            
            if (fileName.includes(searchValue) || filePath.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html>