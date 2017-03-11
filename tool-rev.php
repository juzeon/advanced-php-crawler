<?php
set_time_limit(0);
if ($argc < 2) {
	echo <<<EOF

==========
新浪博客爬虫-网址文件反转工具
-将某个网址文件里面的url全部反转过来，可用于处理新旧文章顺序等
使用方法：
php $argv[0] <网址文件>
参数解释：
<网址文件>：一行一个网址，请使用电脑版访问后复制
命令示例：
php $argv[0] urls.txt
网址文件示例：
http://blog.sina.com.cn/s/xxx_123456wsla.html
http://blog.sina.com.cn/s/xxx_789456wsex.html
==========

EOF;
	exit ;
}
$format = $argv[2];
$urls = file_get_contents($argv[1]);
if (!$urls) {
	echo '无法读取网址文件 ' . $argv[1] . PHP_EOL;
	exit ;
}
echo '#开始处理：' . $argv[1] . PHP_EOL;
$urls = explode(PHP_EOL, $urls);
$new_urls=array_reverse($urls);
$f = fopen($argv[1], 'w');
foreach($new_urls as $item){
	fwrite($f,$item.PHP_EOL);
}
fclose($f);
echo '#处理完毕，已覆盖写入文件' . PHP_EOL . '---------' . PHP_EOL;
echo '#你现在可以使用sina-article.php来爬取文章内容，或者用sina-list.php来爬取文章列表了' . PHP_EOL . '===========' . PHP_EOL . PHP_EOL;