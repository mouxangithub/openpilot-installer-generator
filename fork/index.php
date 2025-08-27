<?php
error_reporting(E_ALL ^ E_WARNING);

# Constants
define("USER_AGENT", $_SERVER['HTTP_USER_AGENT']);
define("IS_NEOS", str_contains(USER_AGENT, "NEOSSetup"));
define("IS_AGNOS", str_contains(USER_AGENT, "AGNOSSetup"));
define("IS_WGET", str_contains(USER_AGENT, "Wget"));
# Use release2 if NEOS, else release3 (careful! wget assumes comma three)
define("DEFAULT_STOCK_BRANCH", IS_NEOS ? "release2" : "release3");

define("WEBSITE_URL", "https://mouxan.cn");
define("BASE_DIR", "");

function logData() {
    global $url;
    global $username;
    global $branch;
    date_default_timezone_set('America/Chicago');

    $data = array("IP" => $_SERVER['REMOTE_ADDR'], "url" => $url, "username" => $username, "branch" => $branch, "is_neos" => IS_NEOS, "is_agnos" => IS_AGNOS, "is_wget" => IS_WGET, "user_agent" => USER_AGENT, "date" => date("Y-m-d_H:i:s",time()));
    $data = json_encode($data);

    $fp = fopen("log.txt", "a");
    fwrite($fp, $data."\n");
    fclose($fp);
}

$url = "/";
if (array_key_exists("url", $_GET)) {
    $url = $_GET["url"];
}

list($username, $branch, $loading_msg, $burl) = explode("/", $url);  # todo: clip these strings at the max length in index (to show up on the webpage)

$username = substr(strtolower($username), 0, 39);  # max GH username length
$branch = substr(trim($branch), 0, 255);  # max GH branch
$branch = $branch == "_" ? "" : $branch;
$loading_msg = substr(trim($loading_msg), 0, 39);
$supplied_loading_msg = $loading_msg != "";  # to print secret message
$burl = substr(trim($burl), 0, 255);  # max GH burl
$burl = $burl != "" ? $branch : "https://github.com";
$repo_name = "openpilot";  # TODO: repo name not yet supported for installation

class Alias {
    public $name, $default_branch, $aliases, $repo, $loading_msg;
    public function __construct($name, $default_branch, $aliases, $repo, $loading_msg) {
        $this->name = $name;  # actual GitHub username
        $this->default_branch = $default_branch;
        $this->aliases = $aliases;
        $this->repo = $repo;  # name of actual repo
        $this->loading_msg = $loading_msg;
    }
}

# Handle aliases
$aliases = [new Alias("dragonpilot-community", "release3", ["dragonpilot", "dp"], "", "dragonpilot"),
            new Alias("commaai", DEFAULT_STOCK_BRANCH, ["stock", "commaai", "a", "c", "op", "cm", "ai"], "", "openpilot"),
            new Alias("sshane", "SA-master", ["shane", "smiskol", "sa", "sshane"], "", "openpilot"),
	        new Alias("sunnypilot", "release-c3", ["sunnypilot", "sp", "sunnyhaibin", "release", "release-c3"], "", "sunnypilot"),
	        new Alias("sunnypilot", "staging-c3	", ["staging-c3", "staging", "sg"], "", "sunnypilot"),
	        new Alias("sunnypilot", "dev-c3	", ["dev-c3", "dev"], "", "sunnypilot"),
	        new Alias("mouxangithub", "master", ["master", "master-new", "msp", "m"], "", "openpilot"),
	        new Alias("mouxangithub", "C3", ["c3", "C3", "tn", "Tn"], "", "openpilot"),
	        new Alias("mouxangithub", "cp", ["mx-carrot", "mouxan-carrot", "mcarrot", "mcp"], "", "openpilot"),
	        new Alias("mouxangithub", "fp", ["mx-frogpilot", "mouxan-frogpilot", "mfp"], "", "openpilot"),
	        new Alias("ajouatom", "carrot2-v8", ["carrot", "carrot2-v8", "carrotV8", "cp"], "", "openpilot"),
	        new Alias("FrogAi", "FrogPilot", ["fp", "FrogPilot", "frog", "frogpilot"], "", "FrogPilot"),
	        new Alias("BluePilotDev", "bp-4.0", ["bp", "bp-4.0", "bp4", "bp4.0"], "", "bluepilot"),
	        new Alias("mouxangithub", "tskm", ["tsk", "tskm"], "", "openpilot")];
foreach ($aliases as $al) {
    if (in_array($username, $al->aliases)) {
        $username = $al->name;
        if ($branch == "") $branch = $al->default_branch;  # if unspecified, use default
        if ($loading_msg == "") $loading_msg = $al->loading_msg;
        if ($al->repo != "") $repo_name = $al->repo;  # in case the fork's name isn't openpilot and redirection doesn't work
        break;
    }
}
if ($loading_msg == "") {  # if not an alias with custom msg and not specified use username
    $loading_msg = $username;
} else {  # make sure we encode spaces, neos setup doesn't like spaces (branch and username shouldn't have spaces)
	$loading_msg = str_replace(" ", "%20", $loading_msg);
}

logData();

$build_script = IS_NEOS ? "/build_neos.php" : "/build_agnos.php";
if (IS_NEOS or IS_AGNOS or IS_WGET) {  # if NEOS or wget serve file immediately. commaai/stock if no username provided
    if ($username == "") {
        $username = "mouxangithub";
        $branch = "master";
        $loading_msg = "openpilot";
    }
    header("Location: " . BASE_DIR . $build_script . "?username=" . $username . "&branch=" . $branch . "&loading_msg=" . $loading_msg);
    return;
}

# Draws visual elements for website
echo '<head>
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<style>
body {background-image: linear-gradient(#F9DEC9, #99B2DD); font-family: "Roboto", sans-serif; color: #30323D; text-align: center;}
span { color: #6369D1; }
a { text-decoration: none; color: #6369D1;}
button[name="download_neos"] {background-color: #cb99c5; border-radius: 4px; border: 5px; padding: 10px 12px; box-shadow:0px 4px 0px #AD83A8; display: inline-block; color: white;top: 1px; outline: 0px transparent !important;}
button:active[name="download_neos"] {border-radius: 4px; border: 5px; padding: 10px 12px; box-shadow:0px 2px 2px #BA8CB5; background-color: #BA8CB5; display: inline-block; top: 1px, outline: 0px transparent !important;}

button[name="download_agnos"] {background-color: #ace6df; border-radius: 4px; border: 5px; padding: 10px 12px; box-shadow:0px 4px 0px #80c2ba; display: inline-block; color: #30323D;top: 1px; outline: 0px transparent !important;}
button:active[name="download_agnos"] {border-radius: 4px; border: 5px; padding: 10px 12px; box-shadow:0px 2px 2px #89c7c7; background-color: #89c7c7; display: inline-block; top: 1px, outline: 0px transparent !important;}
</style>
<title>openpilot安装器</title>
<link rel="icon" type="image/x-icon" href="' . BASE_DIR . '/favicon.ico">
</head>';

echo '</br></br><a href="' . BASE_DIR . '"><h1 style="color: #30323D;">🍴 openpilot安装器 🍴</h1></a>';
echo '<h3 style="position: absolute; bottom: 20px; left: 0; width: 100%; text-align: center;"><a href="https://github.com/sshane/openpilot-installer-generator" style="color: 30323D;">💾 Openpilot安装器Github开源地址</a></h3>';
echo '<div style="position: absolute; bottom: 5px; left: 0; width: 100%; text-align: center;font-size: 14px"><a href="https://beian.miit.gov.cn" style="color: 30323D;">ICP备案号：粤ICP备2025381912号</a></div>';

if ($username == "") {
    echo '<h3 style="color: #30323D;">🎉 现在已经支持Comma 3设备 🎉<h3>';
    echo "</br><h2>在设置过程中，将此URL输入到您的设备中，按照以下格式：</h2>";
    echo "<h2><a href='" . BASE_DIR . "/mouxangithub/master'><span>" . WEBSITE_URL . BASE_DIR . "/username/branch</span></a></h2>";
    echo "</br><h3>或者在桌面上完成请求使用下载自定义安装程序脚本。</h3>";
    exit;
}

echo '<h3>GitHub作者: <a href="https://github.com/' . $username . '/' . $repo_name . '">' . $username . '</a></h3>';


if ($branch != "") {
    echo '<h3>Openpilot分支: <a href="https://github.com/'.$username.'/' . $repo_name . '/tree/'.$branch.'">' . $branch . '</a></h3>';
} else {
    echo '<h3>❗ 没有提供分支，Git 将使用默认的 GitHub 分支 ❗</h3>';
}

if ($loading_msg != "" and $supplied_loading_msg) {
    echo '<h3>你发现了一个隐藏的秘密！</br>使用这个二进制文件时，会显示这条自定义消息：<span>安装 ' . $loading_msg . '</span></h3>';
}

echo '<html>
    <body>
        <form method="post">
        <button class="button" name="download_neos">下载安卓安装包(C2)</button>
        <button class="button" name="download_agnos">下载AGNOS安装包(C3)</button>
    </form>
    <h5>或在你的设备的设置界面上输入此链接。</h5>
    </body>
</html>';

if(array_key_exists('download_neos', $_POST)) {
    header("Location: " . BASE_DIR . "/build_neos.php?username=" . $username . "&branch=" . $branch . "&loading_msg=" . $loading_msg . "&url=" . $burl);
    exit;
}
if(array_key_exists('download_agnos', $_POST)) {
    header("Location: " . BASE_DIR . "/build_agnos.php?username=" . $username . "&branch=" . $branch . "&loading_msg=" . $loading_msg);
    exit;
}
?>
