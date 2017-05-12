<?php
function workUrl($url){
	$http=file_get_contents($url);
	$blogUrls='';
	$titles='';
	preg_match_all('/http:\/\/blog.sina.com.cn\/s\/blog_[^"]+/',$http,$blogUrls);
	preg_match_all('/<span class="atc_title">([\s\S]+?)<\/span>/',$http,$titles);
	return array('blogUrls'=>$blogUrls,'titles'=>$titles);
}
set_time_limit(0);
if ($argc < 2) {
	echo <<<EOF

==========
新浪博客爬虫-列表爬虫
-可以集合已知文章目录（/s/articlelist*）里面的文章列表
使用方法：
php $argv[0] <网址文件>
参数解释：
<网址文件>：一行一个网址，请使用电脑版访问后复制
命令示例：
php $argv[0] urls.txt
网址文件示例：
http://blog.sina.com.cn/s/articlelist_123456wsla.html
http://blog.sina.com.cn/s/articlelist_789456wsex.html
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
$name=explode('.',$argv[1])[0];
$urls = explode(PHP_EOL, $urls);
$name = 'LIST+'.preg_replace('/(\/|\\\)/','-',$name).'.txt';
if(file_exists($name)){
	unlink($name);
	echo '检测到此LIST存在，已经删除旧的文件' . PHP_EOL;
}
echo PHP_EOL.PHP_EOL;
foreach ($urls as $url) {
	if (empty($url)) {
		continue;
	}
	echo '#现在处理：' . $url . PHP_EOL;
	for ($i = 0; $i < 5; $i++) {
		$result = workUrl($url);
		if(empty($result['blogUrls'][0][0])){
			if($i==4){
				echo '##ERROR：正则匹配失败，且超出5次重试次数，请打开网页检查问题。' . PHP_EOL . '---------' . PHP_EOL;
				break;
			}
			echo '##ERROR：正则匹配失败！重试次数'.($i+1) . PHP_EOL;
			sleep(2);
			continue;
		}
		echo '##本页文章：'.PHP_EOL;
		$f = fopen($name, 'a');
		for($j=0;$j<count($result['blogUrls'][0]);$j++){
			$title=trim(str_replace('&nbsp;','',strip_tags($result['titles'][0][$j])));
			$blogUrl=trim($result['blogUrls'][0][$j]);
			fwrite($f,$blogUrl.PHP_EOL);
			echo '['.$title.']('.$blogUrl.')'.PHP_EOL;
		}
		fclose($f);
		echo '#写入数据完毕' . PHP_EOL . '---------' . PHP_EOL;
		break;
	}
}
echo '#输出文件：'. $name . PHP_EOL;
echo '#全部任务处理完毕' . PHP_EOL . '---------' . PHP_EOL;
echo '#你现在可以使用sina-article.php来爬取文章内容' . PHP_EOL . '===========' . PHP_EOL . PHP_EOL;