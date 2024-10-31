=== QQ Avatar ===
Contributors: allarem
Tags: avatar,qq,头像
Requires at least: 3.1
Tested up to: 3.3.1
Stable tag: 0.3.5
License: GPLv2 or later

Replace Gravatar while commenter use QQ numeric email address.如果用户使用数字QQ邮箱留言且公开空间头像，就替换成QQ头像。

== Description ==
Replace Gravatar while commenter use QQ numeric email address.如果用户使用数字QQ邮箱留言且公开空间头像，就替换成QQ头像。

== Installation ==

Upload the plugin to your blog, Activate it.
You've done it !

== Changelog ==

= 0.3.5 =
* BugFIX:check file exit before deletion of expired cache.

= 0.3.4 =
ADD:Check Disk space while can't write cache file and warn the admin.
ADD:if direction of the cache cant be made then warn the admin to check their permisson.
ADD:A month cachetime for ANY cached Avatar has been storaged.

= 0.3.3 =
FIX:qq avatar show warning while there is new qq number login

= 0.3.2 =
* IMPROVE: Won't request info again while request failed last time.
* IMPROVE: Made request timeout to 4 seconds for those server not in China

= 0.3.1 =
* FIX:Fatal error while server can't connect to QQ avatar server
* ADD:return the error message to the HTML quote.

= 0.3 =
* FIXED:avatar won't show while in wp-admin panel
* IMPROVE:Set 1 sencond as timeout to improve performance.

= 0.2 =
Add:md5 qq uin to protect privacy

= 0.1 =
Start to code...
