# 废弃
继续使用之前的workflow。编辑原库currencies_utilities.py中fetch_currencies函数直接返回True，即可避免工具访问被墙的dropbox

# rate-for-alfred

之前一直使用一个叫[Rates](https://github.com/kennedyoliveira/alfred-rates)的Alfred workflow，但是后来作者升级新版本后用到的链接被墙了，所以我用php写了一个简化版，数据来源为百度的汇率换算工具。

##语法
rate 数量 源货币 目标货币(默认为CNY)  
例如: rate 10 USD CNY
