<?php
error_reporting(E_ALL ^ E_WARNING);

// --- Polyfill for str_contains() (PHP < 8.0) ---
if (!function_exists('str_contains')) {
    function str_contains (string $haystack, string $needle): bool
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}

# Constants
define("USER_AGENT", $_SERVER['HTTP_USER_AGENT']);
define("IS_NEOS", str_contains(USER_AGENT, "NEOSSetup"));
define("IS_AGNOS", str_contains(USER_AGENT, "AGNOSSetup"));
define("IS_WGET", str_contains(USER_AGENT, "Wget"));
# Use release2 if NEOS, else release3 (careful! wget assumes comma three)
define("DEFAULT_STOCK_BRANCH", IS_NEOS ? "release2" : "release3");

define("WEBSITE_URL", (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]");
define("BASE_DIR", "");

# 处理URL参数
$url = "";
if (array_key_exists("url", $_GET)) {
    $url = ltrim($_GET['url'], '/');  // 关键修改：去除开头的斜杠
}

list($branch) = explode("/", $url);   # todo: clip these strings at the max length in index (to show up on the webpage)

$branch = $branch ? substr(trim($branch), 0, 255) : '';
$branch = $branch == "_" ? "" : $branch;

class Alias
{
    public $default_branch, $aliases;
    public function __construct($default_branch, $aliases)
    {
        $this->default_branch = $default_branch;
        $this->aliases = $aliases;
    }
}

# Handle aliases
$aliases = [
    new Alias("s3-tici", ['s3-tici', 's3', 's3-tici']),
    new Alias("c3-tici", ["c3", "C3", 'c3-tici']),
    new Alias("master-tici", ["master", "sp", 'master-tici']),
    new Alias("dp", ["dragonpilot", "dp"]),
    new Alias("fp", ["frogpilot", "fp"])
];
foreach ($aliases as $al) {
    if (in_array($branch, $al->aliases)) {
        $branch = $al->default_branch;  # if unspecified, use default
        break;
    }
}

// 如果是请求下载安装程序，则执行构建逻辑
if (array_key_exists('download_agnos', $_POST) || IS_NEOS || IS_AGNOS || IS_WGET) {
    if ($branch == "") {
        $branch = "dp";
    }
    
    // 构建并下载安装程序
    build_and_download_installer($branch);
    exit;
}

// Draws visual elements for website
echo '<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* 基础样式 */
body {
    background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
    font-family: "Roboto", sans-serif; 
    color: #e0f7fa; 
    text-align: center;
    margin: 0;
    padding: 15px;
    min-height: 100vh;
    box-sizing: border-box;
    position: relative;
    overflow-x: hidden;
}

/* 科技感字体 */
h1, h2, h3 {
    font-family: "Orbitron", sans-serif;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* 背景网格动画 */
body::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        linear-gradient(rgba(0, 150, 255, 0.1) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 150, 255, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    animation: gridMove 20s linear infinite;
    z-index: -1;
}

@keyframes gridMove {
    0% { background-position: 0 0; }
    100% { background-position: 20px 20px; }
}

/* 霓虹效果标题 */
h1 {
    font-size: 1.8rem;
    margin: 15px 0;
    color: #00f3ff;
    text-shadow: 
        0 0 3px #00f3ff,
        0 0 6px #00f3ff,
        0 0 12px #00f3ff;
    animation: neonPulse 2s infinite alternate;
    position: relative;
    padding: 10px 0;
}

@keyframes neonPulse {
    from { text-shadow: 0 0 3px #00f3ff, 0 0 6px #00f3ff, 0 0 12px #00f3ff; }
    to { text-shadow: 0 0 5px #00f3ff, 0 0 10px #00f3ff, 0 0 20px #00f3ff; }
}

/* 链接样式 */
a { 
    text-decoration: none; 
    color: #00f3ff;
    position: relative;
    transition: all 0.3s ease;
}

a:hover {
    color: #ffffff;
    text-shadow: 0 0 5px #00f3ff;
}

/* 仪表盘容器 */
.dashboard {
    background: rgba(10, 25, 47, 0.8);
    border: 1px solid #00f3ff;
    border-radius: 12px;
    padding: 20px 15px;
    margin: 20px auto;
    max-width: 700px;
    box-shadow: 
        0 0 15px rgba(0, 243, 255, 0.3),
        inset 0 0 8px rgba(0, 243, 255, 0.2);
    backdrop-filter: blur(5px);
    position: relative;
    overflow: hidden;
}

.dashboard::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #00f3ff, transparent);
}

@keyframes scanLine {
    0% { top: 0; }
    100% { top: 100%; }
}

/* 信息卡片 */
.info-card {
    background: rgba(20, 40, 60, 0.7);
    border: 1px solid rgba(0, 243, 255, 0.3);
    border-radius: 8px;
    padding: 12px;
    margin: 12px 0;
    transition: all 0.3s ease;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(0, 243, 255, 0.4);
    border-color: rgba(0, 243, 255, 0.6);
}

h3 {
    font-size: 1.1rem;
    margin: 10px 0;
    color: #64feda;
    text-shadow: 0 0 3px rgba(100, 254, 218, 0.5);
}

/* 按钮样式 - 汽车科技风格 */
button[name^="download"] {
    background: linear-gradient(145deg, #0a192f, #00f3ff);
    border: none;
    padding: 14px 20px;
    border-radius: 30px;
    color: #0a192f;
    font-family: "Orbitron", sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    overflow: hidden;
    margin: 12px auto;
    box-shadow: 
        0 0 10px rgba(0, 243, 255, 0.5),
        0 3px 10px rgba(0, 0, 0, 0.3);
    display: block;
    width: 95%;
    max-width: 400px;
}

button[name^="download"]::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: 0.5s;
}

button[name^="download"]:hover::before {
    left: 100%;
}

button[name^="download"]:hover {
    transform: translateY(-2px);
    box-shadow: 
        0 0 15px rgba(0, 243, 255, 0.8),
        0 5px 15px rgba(0, 0, 0, 0.4);
}

button[name^="download"]:active {
    transform: translateY(1px);
    box-shadow: 
        0 0 8px rgba(0, 243, 255, 0.6),
        0 2px 10px rgba(0, 0, 0, 0.3);
}

/* 复制链接框样式 */
.copy-box {
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(0, 243, 255, 0.5);
    border-radius: 8px;
    padding: 15px;
    margin: 15px 0;
    position: relative;
}

.copy-box input {
    width: 100%;
    padding: 12px;
    background: rgba(0, 20, 40, 0.7);
    border: 1px solid rgba(0, 243, 255, 0.3);
    border-radius: 6px;
    color: #64feda;
    font-family: "Roboto", monospace;
    font-size: 0.9rem;
    text-align: center;
    outline: none;
    box-sizing: border-box;
    cursor: pointer; /* 显示手指光标 */
}

.copy-box input:focus {
    border-color: #00f3ff;
    box-shadow: 0 0 10px rgba(0, 243, 255, 0.5);
}

.copy-notification {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 243, 255, 0.9);
    color: #0a192f;
    padding: 10px 20px;
    border-radius: 30px;
    font-family: "Orbitron", sans-serif;
    font-weight: 600;
    box-shadow: 0 0 20px rgba(0, 243, 255, 0.8);
    z-index: 1000;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.copy-notification.show {
    opacity: 1;
}

/* 速度表盘样式 */
.speedometer {
    width: 80px;
    height: 80px;
    border: 2px solid #00f3ff;
    border-radius: 50%;
    margin: 0 auto 15px;
    position: relative;
    background: rgba(10, 25, 47, 0.7);
    box-shadow: 
        inset 0 0 15px rgba(0, 243, 255, 0.3),
        0 0 10px rgba(0, 243, 255, 0.2);
}

.speedometer::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 6px;
    height: 30px;
    background: #00f3ff;
    transform-origin: bottom center;
    transform: translate(-50%, -100%) rotate(45deg);
    animation: speedRotate 3s ease-in-out infinite alternate;
}

@keyframes speedRotate {
    0% { transform: translate(-50%, -100%) rotate(0deg); }
    100% { transform: translate(-50%, -100%) rotate(90deg); }
}

/* 雷达扫描效果 */
.radar {
    position: fixed;
    top: 50%;
    left: 50%;
    width: 200px;
    height: 200px;
    border: 1px solid rgba(0, 243, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    z-index: -1;
    opacity: 0.2;
}

.radar::before {
    content: "";
    position: absolute;
    top: 0;
    left: 50%;
    width: 1px;
    height: 50%;
    background: linear-gradient(to bottom, #00f3ff, transparent);
    transform-origin: bottom center;
    animation: radarScan 4s linear infinite;
}

@keyframes radarScan {
    0% { transform: translateX(-50%) rotate(0deg); }
    100% { transform: translateX(-50%) rotate(360deg); }
}

/* 数据流效果 */
.data-stream {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: -1;
}

.data-point {
    position: absolute;
    width: 2px;
    height: 2px;
    background: #00f3ff;
    border-radius: 50%;
    animation: dataFlow var(--duration) linear infinite;
    opacity: 0;
}

@keyframes dataFlow {
    0% { 
        transform: translateY(-20px) translateX(var(--offset)); 
        opacity: 0;
    }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { 
        transform: translateY(100vh) translateX(var(--offset)); 
        opacity: 0;
    }
}

/* 移动端适配 */
@media screen and (max-width: 768px) {
    body {
        padding: 10px 5px;
    }
    
    h1 {
        font-size: 1.5rem;
        margin: 10px 0;
    }
    
    .dashboard {
        padding: 15px 10px;
        margin: 15px auto;
    }
    
    h3 {
        font-size: 1rem;
        margin: 8px 0;
    }
    
    button[name^="download"] {
        padding: 12px 15px;
        font-size: 0.85rem;
        width: 98%;
        margin: 10px auto;
    }
    
    .speedometer {
        width: 60px;
        height: 60px;
    }
    
    .copy-box input {
        padding: 10px;
        font-size: 0.8rem;
    }
}

@media screen and (max-width: 480px) {
    h1 {
        font-size: 1.3rem;
    }
    
    h3 {
        font-size: 0.9rem;
    }
    
    button[name^="download"] {
        padding: 10px 12px;
        font-size: 0.8rem;
    }
    
    .dashboard {
        padding: 12px 8px;
    }
    
    .copy-box {
        padding: 12px;
    }
}

/* 加载动画 */
.loading {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255,255,255,.3);
    border-radius: 50%;
    border-top-color: #00f3ff;
    animation: spin 1s ease-in-out infinite;
    margin-left: 8px;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
<title>🚗 openpilot 安装器' . ($branch != "" ? ' - ' . $branch : '') . '</title>
<link rel="icon" type="image/x-icon" href="' . BASE_DIR . '/favicon.png">
</head>';

echo '<body>';
echo '<div class="radar"></div>';
echo '<div class="data-stream" id="dataStream"></div>';

echo '<div class="dashboard" style="user-select: none; cursor: pointer;" onclick="handleCardClick(\'/\')">';
echo '<h1>🚗 openpilot 安装器</h1>';
echo '</div>';

if ($branch != "") {
    echo '<div class="dashboard" style="user-select: none; cursor: pointer;" onclick="handleCardClick(\'https://gitee.com/mouxangitee/openpilot/tree/' . $branch . '\')" style="cursor: pointer;">';
    echo '<h3>分支: ' . $branch . '</h3>';
    echo '</div>';
    echo '<div class="dashboard">';
    // 添加可复制的链接框（去掉协议前缀）
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $clean_url = str_replace(['http://', 'https://'], '', $current_url);
    echo '<div class="info-card" style="user-select: none; cursor: pointer;" onclick="copyLink()" id="copyLink">' . htmlspecialchars($clean_url) . '</div>';

    echo '<h5>请在您的Comma设备中输入此链接，或点击下方按钮连接设备下载</h5>';

    echo '<form method="post">';
    echo '<button type="submit" name="download_agnos">🚀 下载' . htmlspecialchars($branch) . '安装包</button>';
    echo '</form>';
    echo '</div>';
}

echo '<div class="dashboard">';
echo '<h3>不支持C2，目前已支持的分支:</h3>';
echo '<button name="download" onclick="handleCardClick(\'/dp\')">🐉 dragonpilot - 0.10.1 (推荐)</button>';
echo '<button name="download" onclick="handleCardClick(\'/fp\')">🐸 frogpilot - 0.9.8</button>';
echo '<button name="download" onclick="handleCardClick(\'/s3\')">☀️ sunnypilot - s3-tici</button>';
echo '<button name="download" onclick="handleCardClick(\'/master\')">☀️ sunnypilot - master-tici</button>';
echo '<button name="download" onclick="handleCardClick(\'/c3\')">☀️ sunnypilot - c3-tici</button>';
echo '</div>';

echo '<div class="dashboard" style="user-select: none; cursor: pointer;" onclick="handleCardClick(\'/fork\')">💾 原版安装器（支持c2）</div>';

echo '<div class="dashboard" style="user-select: none; cursor: pointer;" onclick="handleCardClick(\'https://github.com/mouxangithub/openpilot-installer-generator\')">💾 本项目开源地址</div>';

// 添加复制通知元素
echo '<div class="copy-notification" id="copyNotification">已复制!</div>';

echo '<script>
function handleCardClick(url) {
    window.location.href = url;
}

// 创建数据流效果
function createDataStream() {
    const container = document.getElementById("dataStream");
    
    for (let i = 0; i < 30; i++) {
        const point = document.createElement("div");
        point.className = "data-point";
        point.style.left = Math.random() * 100 + "%";
        point.style.top = Math.random() * 100 + "%";
        point.style.setProperty("--duration", (Math.random() * 8 + 3) + "s");
        point.style.setProperty("--offset", (Math.random() * 50 - 25) + "px");
        container.appendChild(point);
    }
}

// 复制链接功能
function copyLink() {
    const copyText = document.getElementById("copyLink").textContent;
    
    navigator.clipboard.writeText(copyText).then(() => {
        // 显示复制成功通知
        const notification = document.getElementById("copyNotification");
        notification.classList.add("show");
        
        // 3秒后隐藏通知
        setTimeout(() => {
            notification.classList.remove("show");
        }, 3000);
    }).catch(err => {
        console.error("复制失败: ", err);
        // 显示错误通知
        const notification = document.getElementById("copyNotification");
        notification.textContent = "复制失败";
        notification.classList.add("show");
        
        setTimeout(() => {
            notification.classList.remove("show");
            notification.textContent = "已复制!";
        }, 3000);
    });
}

// 页面加载完成后创建效果
document.addEventListener("DOMContentLoaded", function() {
    createDataStream();
});
</script>';

echo '</body>';

// 构建并下载安装程序的函数
function build_and_download_installer($branch) {
    define("PI", "314159265358979323846264338327950288419");  # placeholder for loading msg
    define("GOLDEN", "161803398874989484820458683436563811772030917980576286213544862270526046281890244970720720418939113748475408807538689175212663386222353693179318006076672635443338908659593958290563832266131992829026788067520876689250171169620703222104321626954862629631361");  # placeholder for branch

    # Replaces placeholder with input + any needed NULs, plus does length checking
    function fill_in_arg($placeholder, $replace_with, $binary, $padding, $arg_type) {
        $placeholder_len = mb_strlen($placeholder);
        if ($placeholder_len - strlen($replace_with) < 0) { echo "Error: Invalid " . $arg_type . " length!"; exit; }

        $replace_with .= str_repeat($padding, $placeholder_len - strlen($replace_with));
        return str_replace($placeholder, $replace_with, $binary);
    }


    # Load installer binary
    $installer_binary = file_get_contents(getcwd() . "/openpilot");  # load the unmodified installer

    # Handle branch replacement (3 occurrences):
    $installer_binary = fill_in_arg(GOLDEN, $branch, $installer_binary, "\0", "branch");

    # Handle loading message replacement:
    $installer_binary = fill_in_arg(PI, $branch, $installer_binary, " ", "loading message");  // QT actually displays null characters


    # Now download
    header("Content-Type: application/octet-stream");
    header("Content-Length: " . strlen($installer_binary));  # we want actual bytes
    header("Content-Disposition: attachment; filename=" . $branch);
    echo $installer_binary;  # downloads without saving to a file
}
?>