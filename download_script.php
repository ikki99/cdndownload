<?php
// 设置执行时间，防止大文件下载超时
set_time_limit(300);

// 初始化变量
$success_count = 0;
$fail_count = 0;
$fail_list = [];
$downloaded_files = []; // 用于跟踪已下载的文件，防止重复下载

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
    function downloadFile($url, $local_path, $base_url) {
        global $downloaded_files, $success_count, $fail_count, $fail_list;
        
        // 移除文件名中的参数部分
        $local_path = preg_replace('/([^?#]+).*/', '$1', $local_path);
        
        // 检查文件是否已经下载过
        if (isset($downloaded_files[$local_path])) {
            return true;
        }

        $dir = dirname($local_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = @file_get_contents($url);
        if ($content !== false) {
            file_put_contents($local_path, $content);
            $downloaded_files[$local_path] = true;
            $success_count++;
            
            // 检查文件类型
            $ext = strtolower(pathinfo($local_path, PATHINFO_EXTENSION));
            if ($ext == 'css') {
                processCSS($content, $local_path, $base_url);
            } elseif ($ext == 'js') {
                processJS($content, $local_path, $base_url);
            }
            
            return true;
        } else {
            $fail_count++;
            $fail_list[] = $url;
            return false;
        }
    }

    // 处理 CSS 文件
    function processCSS($content, $file_path, $base_url) {
        global $local_base_path;
        // 匹配 CSS 中的 url() 引用
        preg_match_all('/url\([\'"]?([^\'"]+)[\'"]?\)/i', $content, $matches);
        foreach ($matches[1] as $resource) {
            if (!preg_match('/^(https?:)?\/\//i', $resource)) {
                $resource_url = $base_url . dirname(str_replace($local_base_path, '', $file_path)) . '/' . $resource;
                $resource_path = dirname($file_path) . '/' . $resource;
                downloadFile($resource_url, $resource_path, $base_url);
            }
        }
        
        // 匹配 @import 规则
        preg_match_all('/@import\s+[\'"]([^\'"]+)[\'"]/i', $content, $imports);
        foreach ($imports[1] as $import) {
            if (!preg_match('/^(https?:)?\/\//i', $import)) {
                $import_url = $base_url . dirname(str_replace($local_base_path, '', $file_path)) . '/' . $import;
                $import_path = dirname($file_path) . '/' . $import;
                downloadFile($import_url, $import_path, $base_url);
            }
        }
    }

    // 处理 JS 文件
    function processJS($content, $file_path, $base_url) {
        global $local_base_path;
        // 匹配 JS 中可能的资源引用（如 .map 文件）
        if (preg_match('/\/\/[#@]\s*sourceMappingURL=(.+)/', $content, $match)) {
            $map_file = $match[1];
            if (!preg_match('/^(https?:)?\/\//i', $map_file)) {
                $map_url = $base_url . dirname(str_replace($local_base_path, '', $file_path)) . '/' . $map_file;
                $map_path = dirname($file_path) . '/' . $map_file;
                downloadFile($map_url, $map_path, $base_url);
            }
        }
    }

    // 下载文件
    foreach ($files_to_download as $file) {
        $file = trim($file);
        if (!empty($file)) {
            $url = $base_url . $file;
            $local_path = $local_base_path . $file;
            downloadFile($url, $local_path, $base_url);
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
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --danger-color: #e74c3c;
            --text-color: #333;
            --bg-color: #f4f4f4;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--bg-color);
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .summary {
            background-color: #e8f4fd;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .summary p {
            margin: 10px 0;
            font-size: 18px;
        }
        
        .fail-list {
            background-color: #fde8e8;
            border-left: 5px solid var(--danger-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .fail-list h3 {
            color: var(--danger-color);
            margin-top: 0;
        }
        
        .fail-list ul {
            padding-left: 20px;
        }
        
        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .button:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>下载结果</h1>
        
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
            <div class="summary">
                <p>总共下载: <?php echo $success_count + $fail_count; ?> 个文件（包括附加文件）</p>
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
