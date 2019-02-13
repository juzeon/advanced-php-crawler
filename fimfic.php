<?php
date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);
if ($argc < 2) {
	echo <<<EOF

==========
FimFiction爬虫
-输入fimfiction.net的Story网址，抓取、翻译（保留英文原文）并生成电子书。
使用方法：
php $argv[0] <Story网址>
命令示例：
php $argv[0] https://www.fimfiction.net/story/318771/earth-without-us
==========

EOF;
	exit ;
}
$url = $argv[1];
if (!$url) {
	echo '网址为空 ' . $argv[1] . PHP_EOL;
	exit ;
}
$bookHtml=file_get_contents($url);
preg_match('/\/story\/\d+/',$url,$m);
$storyId=substr($m[0],7);
preg_match('/\/story\/\d+\/(.*)/',$url,$m);
$storyName=$m[1];
preg_match_all('/class="chapter-title" href="(.*?)"/',$bookHtml,$m);
$chapterUrls=$m[1];



if(file_exists($storyName)){
	delDirAndFile($storyName);
	echo '检测到此书本存在，已经删除旧的文件' . PHP_EOL;
}
mkdir($storyName);
try{
	preg_match('/property="og:image" content="(.*?)"/',$bookHtml,$m);
	$coverUrl=$m[1];
	file_put_contents($storyName.'/cover.jpg',file_get_contents($coverUrl));
}catch(Exception $e){
	echo '无法抓取封面' . PHP_EOL;
}
file_put_contents($storyName.'/SUMMARY.md','# 目录'.PHP_EOL.'* [说明](README.md)'.	PHP_EOL);
file_put_contents($storyName.'/README.md','# 说明'.PHP_EOL.'本电子书使用[FimFiction爬虫](https://github.com/juzeon/advanced-php-crawler)创建，祝你阅读愉快！'.PHP_EOL);
echo PHP_EOL.PHP_EOL;
foreach($chapterUrls as $count=>$url) {
	$url='https://www.fimfiction.net'.$url;
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
		$content=$result['content'];
		preg_match_all('/class="user_image" src="(.*?)"/',$content,$img_matches);
		
		if(!empty($img_matches)){
			for($j=0;$j<count($img_matches[1]);$j++){
				echo '##正在存图：'.$img_matches[1][$j]. PHP_EOL;
				for ($ji = 0; $ji < 5; $ji++) {
					$img_content=file_get_contents($img_matches[1][$j]);
					if (empty($img_content)) {
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
					$base64=chunk_split(base64_encode($img_content));
					$content=str_ireplace($img_matches[1][$j],'" >'.PHP_EOL.PHP_EOL.'![img-'.$j.'](data:image/jpg/png/gif;base64,'.$base64.')'.PHP_EOL.PHP_EOL.'<xx x="',$content);
					break;
				}
				
			}
		}
		$content=strip_tags($content);
		$content=str_replace('&nbsp;',' ',$content);
		$content=str_replace('&quot;','"',$content);
		$content=str_ireplace('_', '\\_', $content);
		$content=preg_replace('/		*/',' ',$content);
		$content=preg_replace('/ {4,}/',' ',$content);
		$content=caiyunTranslate($content);
		$f=fopen($storyName.'/'.$count.'.md','w');
		fwrite($f,'# '.$result['title'].PHP_EOL.'### 序号：'.$count.PHP_EOL.$content.PHP_EOL);
		fclose($f);
		$f=fopen($storyName.'/SUMMARY.md','a');
		fwrite($f,'* ['.$result['title'].']('.$count.'.md)'.PHP_EOL);
		fclose($f);
		echo '#写入数据完毕' . PHP_EOL . '---------' . PHP_EOL;
		$count++;
		break;
	}
}
echo '=============='.PHP_EOL.'#开始处理书本依赖文件'.PHP_EOL;
$json_book=array(
'title'=>$storyName,
'description'=>'FimFiction爬虫于'.date('Y年m月d日 H:i').'生成的电子书',
'language'=>'zh-cn',
'author'=>'FimFiction爬虫'
);
$book_json=json_encode($json_book);
file_put_contents($storyName.'/book.json',$book_json);
echo '#全部任务处理完毕' . PHP_EOL . '---------' . PHP_EOL;
echo '#你可以编辑book.json来修改这本书的详细信息，然后——'.PHP_EOL;
echo '#使用gitbook工具输出为mobi格式电子书：gitbook mobi '.$storyName.'/ '.explode('.',$argv[1])[0].'.mobi'.PHP_EOL;
echo '#使用gitbook工具输出为epub格式电子书：gitbook epub '.$storyName.'/ '.explode('.',$argv[1])[0].'.epub'.PHP_EOL;
echo '#注意：需要安装gitbook和calibre/ebook-convert，参考资料：'.PHP_EOL;
echo 'http://www.jianshu.com/p/7476afdd9248'.PHP_EOL;
echo 'https://kindlefere.com/post/288.html#gb_6'.PHP_EOL;
echo '===========' . PHP_EOL . PHP_EOL;


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
	$content='';
	$article=file_get_contents(trim($url));
	preg_match('/(<div class="bbcode">)([\s\S]*?)<style>/', $article, $content);
	$content = $content[0];
	
	
	$content=str_ireplace('`', '\\`', $content);
	$content=str_replace('*','\\*', $content);
	$content=str_replace('~', '\\~', $content);
	$content=preg_replace_callback('/<b>(.*?)<\/b>/',function($m){
		return ' **'.trim($m[1]).'** ';
	},$content);
	$content=preg_replace_callback('/<i>(.*?)<\/i>/',function($m){
		return ' *'.trim($m[1]).'* ';
	},$content);
	$content=preg_replace('/\n*/','',$content);
	$content=str_ireplace('</div>',PHP_EOL.PHP_EOL, $content);
	$content=str_ireplace('</p>',PHP_EOL.PHP_EOL, $content);
	$content=preg_replace('/<[bB][rR][ ]*\/[ ]*>/',PHP_EOL.PHP_EOL,$content);
	
	preg_match('/<title>(.*?)<\/title>/', $article, $title);
	$title = str_replace('&nbsp;', ' ', strip_tags($title[0]));
	$title=str_replace('_','\\_', $title);
	$result['title'] = $title;
	$result['content'] = $content;
	return $result;
}
function caiyunTranslate($content){
	//$sendContent=preg_replace('/!\[img-\d+\]\(data:image\/jpg\/png\/gif;base64,([\s\S]*?)\)/','',$content);
	$brokenText=explode(PHP_EOL.PHP_EOL,$content);
	$sendText=[];
	$returnText=[];
	foreach($brokenText as $paragraph){
		$paragraph=trim($paragraph);
		if(!empty($paragraph)){
			$sendText[]=preg_replace('/!\[img-\d+\]\(data:image\/jpg\/png\/gif;base64,([\s\S]*?)\)/','Image.',$paragraph);
			$returnText[]=$paragraph;
		}
	}
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,'https://api.interpreter.caiyunai.com/v1/translator');
	$request=[
		'source'=>$sendText,
		'trans_type'=>'en2zh',
		'request_id'=>'web_fanyi',
	];
	$requestData=json_encode($request);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('x-authorization: token:cy4fgbil24jucmh8jfr5',
	'Content-Type: application/json',
	'Content-Length:' . strlen($requestData),
	'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:65.0) Gecko/20100101 Firefox/65.0')); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
	curl_setopt($ch, CURLOPT_NOBODY, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$res = curl_exec($ch);
	curl_close($ch);
	$returnString='';
	foreach(json_decode($res)->target as $key=>$item){
		$returnString.=$returnText[$key].PHP_EOL.PHP_EOL.$item.PHP_EOL.PHP_EOL.'&nbsp;'.PHP_EOL.PHP_EOL;
	}
	
	
	return $returnString;
}
