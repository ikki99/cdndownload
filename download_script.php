<?php
// 设置执行时间，防止大文件下载超时
set_time_limit(300);

// 初始化变量
$success_count = 0;
$fail_count = 0;
$fail_list = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取基础 URL
    $base_url = ($_POST['base_url'] === 'custom') ? $_POST['custom_base_url'] : $_POST['base_url'];

    // 确保基础 URL 以斜杠结尾
    $base_url = rtrim($base_url, '/') . '/';

    // 获取文件列表
    $file_list = $_POST['file_list'];
    $files_to_download = explode("\n", str_replace("\r", "", $file_list));

    // 本地保存的基础路径
    $local_base_path = 'cdn/';

    // 函数：下载文件并保存到本地
    function downloadFile($url, $local_path) {
        $dir = dirname($local_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = @file_get_contents($url);
        if ($content !== false) {
            file_put_contents($local_path, $content);
            return true;
        } else {
            return false;
        }
    }

    // 下载文件
    foreach ($files_to_download as $file) {
        $file = trim($file);
        if (!empty($file)) {
            $url = $base_url . $file;
            $local_path = $local_base_path . $file;
            if (downloadFile($url, $local_path)) {
                $success_count++;
            } else {
                $fail_count++;
                $fail_list[] = $file;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>下载结果</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .summary {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e7f3fe;
            border-left: 5px solid #2196F3;
        }
        .fail-list {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 10px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>下载结果</h1>
        
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="summary">
                <p>总共尝试下载: <?php echo $success_count + $fail_count; ?> 个文件</p>
                <p>成功下载: <?php echo $success_count; ?> 个文件</p>
                <p>下载失败: <?php echo $fail_count; ?> 个文件</p>
            </div>

            <?php if ($fail_count > 0): ?>
                <div class="fail-list">
                    <h3>下载失败的文件:</h3>
                    <ul>
                        <?php foreach ($fail_list as $failed_file): ?>
                            <li><?php echo htmlspecialchars($failed_file); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p>没有接收到下载请求。</p>
        <?php endif; ?>

        <p>
              <a href="browse_cdn.php" class="button">浏览 CDN 文件</a>&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;  &nbsp;&nbsp;   
            <a href="download_form.html" class="button">返回下载页面</a>
            
        
        </p>
    </div>
</body>
</html>