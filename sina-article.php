<?php
date_default_timezone_set('Asia/Shanghai');
function delDirAndFile( $dirName )
{
if ( $handle = opendir( "$dirName" ) ) {
while ( false !== ( $item = readdir( $handle ) ) ) {
if ( $item != "." && $item != ".." ) {
if ( is_dir( "$dirName/$item" ) ) {
delDirAndFile( "$dirName/$item" );
} else {
unlink( "$dirName/$item" );
}
}
}
closedir( $handle );
rmdir( $dirName );
}
}
function workUrl($url) {
	/*$client = new SinHttpClient();
	$client -> request -> setHeader('User-Agent', 'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)');
	$client -> get(trim($url));
	$article = $client -> response -> body;*/
	$content='';
	$article=file_get_contents(trim($url));
	if(strpos($article,'<!-- 正文开始 -->')){
		preg_match('/<!-- 正文开始 -->([\s\S]*)<!-- 正文结束 -->/', $article, $content);
	}else{
		preg_match('/<!-- 内容区 -->([\s\S]*)<!--\/内容区-->/', $article, $content);
	}
	//$content = str_replace(PHP_EOL . PHP_EOL, PHP_EOL, strip_tags($content[0]));
	$content = str_replace('　', ' ', $content[0]);
	
	$content=str_ireplace('`', '\\`', $content);
	$content=str_replace('*','\\*', $content);
	$content=str_replace('~', '\\~', $content);
	$content=str_ireplace('<strong>', '**', $content);
	$content=str_ireplace('</strong>', '**', $content);
	$content=str_ireplace('<em>', '*', $content);
	$content=str_ireplace('</em>', '*', $content);
	
	preg_match('/<title>(.*?)_(.*)<\/title>/', $article, $title);
	$title = str_replace('&nbsp;', ' ', strip_tags($title[0]));
	$result['title'] = $title;
	$result['content'] = $content;
	return $result;
}

set_time_limit(0);
if ($argc < 2) {
	echo <<<EOF

==========
新浪博客爬虫-文章爬虫
-可以提取已知文章页面（/s/blog*）里面的文章
使用方法：
php $argv[0] <网址文件>
参数解释：
<网址文件>：一行一个网址，请使用电脑版访问后复制
命令示例：
php $argv[0] urls.txt
网址文件示例：
http://blog.sina.com.cn/s/blog_123456wsla.html
http://blog.sina.com.cn/s/blog_789456wsex.html
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
$name='BOOK+'.preg_replace('/(\/|\\\)/','-',$name);
$urls = explode(PHP_EOL, $urls);
if(file_exists($name)){
	delDirAndFile($name);
	echo '检测到此书本存在，已经删除旧的文件' . PHP_EOL;
}
mkdir($name);
$img_name=$name.'/img';
mkdir($img_name);
file_put_contents($name.'/SUMMARY.md','# 目录'.PHP_EOL.'* [说明](README.md)'.	PHP_EOL);
file_put_contents($name.'/README.md','# 说明'.PHP_EOL.'本电子书使用[新浪博客爬虫](https://github.com/juzeon/advanced-php-crawler)创建，祝你阅读愉快！'.PHP_EOL);
$cover_created=false;
//$dir = preg_replace('/(\/|\\\)/','-',$name).'-'.time();
//mkdir($dir);
echo PHP_EOL.PHP_EOL;
$count=0;
foreach ($urls as $url) {
	if (empty($url)) {
		continue;
	}
	echo '#现在处理：' . $url . PHP_EOL;
	for ($i = 0; $i < 5; $i++) {
		$result = workUrl($url);
		echo '#标题：' . $result['title'] . PHP_EOL;
		if (empty($result['content'])) {
			if($i==4){
				echo '##ERROR：正则匹配失败，且超出5次重试次数，请打开网页检查问题。' . PHP_EOL . '---------' . PHP_EOL;
				break;
			}
			echo '##ERROR：正则匹配失败！重试次数'.($i+1) . PHP_EOL;
			sleep(2);
			continue;
		} else {
			echo '##内容有效' . PHP_EOL;
		}
		//$f = fopen($dir . '/'.$name, 'a');
		//fwrite($f, '###' . $result['title'] . PHP_EOL . str_replace('&nbsp;','',$result['content'])  . PHP_EOL.'--------' . PHP_EOL);
		$content=$result['content'];
		preg_match_all('/real_src ="http[s]*:\/\/(.*)\.sinaimg\.cn\/(.*?)"/',$content,$img_matches);
		
		if(!empty($img_matches)){
			mkdir($img_name.'/'.$count);
			for($j=0;$j<count($img_matches[2]);$j++){
				echo '##正在存图：'.'http://'.$img_matches[1][$j].'.sinaimg.cn/'.$img_matches[2][$j] . PHP_EOL;
				for ($ji = 0; $ji < 5; $ji++) {
					$img_content=file_get_contents('http://'.$img_matches[1][$j].'.sinaimg.cn/'.$img_matches[2][$j]);
					if (empty($img_content)||md5($img_content)=='7bd88df2b5be33e1a79ac91e7d0376b5') {
						if($ji==4){
							echo '##ERROR：正则匹配失败，且超出5次重试次数，自动略过。' . PHP_EOL . '---------' . PHP_EOL;
							break;
						}
						echo '##ERROR：正则匹配失败！重试次数'.($ji+1) . PHP_EOL;
						sleep(2);
						continue;
					} else {
						echo '##图片有效' . PHP_EOL;
					}
					file_put_contents($img_name.'/'.$count.'/'.$j.'.jpg',$img_content);
					if($cover_created==false){
						file_put_contents($name.'/cover.jpg',$img_content);
						$cover_created=true;
					}
					//$content=preg_replace('/real_src ="http[s]*:\/\/'.$img_matches[1][$j].'\.sinaimg\.cn\/'.$img_matches[2][$j].'"/',' >'.'[img-'.$j.']('.$img_name.'/'.$count.'/'.$j.'.jpg)'.'<xx ',$content);
					$content=str_ireplace($img_matches[1][$j].'.sinaimg.cn/'.$img_matches[2][$j],'" >'.PHP_EOL.'![img-'.$j.']('.'img/'.$count.'/'.$j.'.jpg)'.PHP_EOL.'<xx x="',$content);
					break;
				}
				
			}
		}
		$content=str_replace('&nbsp;',' ',strip_tags($content));
		$f=fopen($name.'/'.$count.'.md','w');
		fwrite($f,'# '.$result['title'].PHP_EOL.'### 序号：'.$count.PHP_EOL.$content.PHP_EOL);
		fclose($f);
		$f=fopen($name.'/SUMMARY.md','a');
		fwrite($f,'* ['.$result['title'].']('.$count.'.md)'.PHP_EOL);
		fclose($f);
		echo '#写入数据完毕' . PHP_EOL . '---------' . PHP_EOL;
		$count++;
		break;
	}
}
echo '=============='.PHP_EOL.'#开始处理书本依赖文件'.PHP_EOL;
$json_book=array(
'title'=>explode('.',$argv[1])[0],
'description'=>'新浪博客爬虫于'.date('Y年m月d日 H:i').'生成的电子书',
'language'=>'zh-cn',
'author'=>'新浪博客爬虫'
);
$book_json=json_encode($json_book);
file_put_contents($name.'/book.json',$book_json);
echo '#全部任务处理完毕' . PHP_EOL . '---------' . PHP_EOL;
echo '#你可以编辑book.json来修改这本书的详细信息，然后——'.PHP_EOL;
echo '#使用gitbook工具输出为mobi格式电子书：gitbook mobi '.$name.'/ '.explode('.',$argv[1])[0].'.mobi'.PHP_EOL;
echo '#使用gitbook工具输出为epub格式电子书：gitbook epub '.$name.'/ '.explode('.',$argv[1])[0].'.epub'.PHP_EOL;
echo '#注意：需要安装gitbook和calibre/ebook-convert，参考资料：'.PHP_EOL;
echo 'http://www.jianshu.com/p/7476afdd9248'.PHP_EOL;
echo 'https://kindlefere.com/post/288.html#gb_6'.PHP_EOL;
echo '===========' . PHP_EOL . PHP_EOL;
