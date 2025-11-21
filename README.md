# openpilot-installer-generator
一个基于 PHP 的网页工具，通过字符串替换动态生成二进制文件，可直接在 Agnos 设置中使用。

## 这是什么？
以往安装自定义分支时，用户需要在设备的“自定义软件”选项中输入 `https://openpilot.comma.ai`，然后通过 `ssh` 克隆实际需要的分支。现在，您可以直接在设置阶段通过 URL 安装任何 GitHub 上的 openpilot 分支，而无需先克隆官方版本。

## 使用方法
语法非常简单，最多可传递 3 个参数给生成器网站：`https://mouxan.cn/{分支}`

- `分支`：可选，表示要克隆的分支。

### 参数说明
- 如果 `分支` 留空（如 `https://mouxan.cn/dp`），Git 将克隆 Gitee 上的默认分支。

**示例：** `https://mouxan.cn/dp` 将安装 🐉 dragonpilot - 0.10.1 (推荐) 分支。

`index.php` 文件会根据用户代理（User-Agent）决定提供哪种安装器：
- 如果用户代理包含 `AGNOSSetup`，则提供基于 Ubuntu 的安装器（适用于 comma three）。

## 别名功能
在 `index.php` 文件中定义的别名可以快速安装用户名较长的 fork。

- 安装 [dragonpilot](https://gitee.com/mouxangitee/openpilot/tree/dp) 时，可使用默认分支 `dp`：`https://mouxan.cn/dp`

---
此工具由 [nelsonjchen](https://github.com/nelsonjchen) 在 [comma.ai 社区 Discord](https://discord.comma.ai/) 中提出！感谢 [sshane](https://github.com/sshane) 提供的项目脚本。