<?php
date_default_timezone_set('Asia/Shanghai');
set_time_limit(0);
if ($argc < 2) {
    echo <<<EOF

==========
wenku8爬虫
-输入wenku8.net的BookID，抓取并生成电子书。
使用方法：
php $argv[0] <BookID>
命令示例：
php $argv[0] 1538
==========

EOF;
    exit ;
}
$storyId = $argv[1];
if (!$storyId) {
    echo 'BookID为空 ' . $argv[1] . PHP_EOL;
    exit ;
}
$bookUrl='https://www.wenku8.net/book/'.$storyId.'.htm';
$bookHtml=strToUtf8(file_get_contents($bookUrl));
preg_match('/<title>(.*?)-/',$bookHtml,$m);
$storyName=$m[1];

preg_match('/<a href="(.*?)">小说目录<\/a>/',$bookHtml,$m);
$chaptersPageUrl=$m[1];
$chaptersPageBaseUrl=substr($chaptersPageUrl,0,-9);
$chaptersHtml=strToUtf8(file_get_contents($chaptersPageUrl));

$tmpHtml=explode('<td class="vcss" colspan="4">',$chaptersHtml);
$chapterUrls=[];
foreach ($tmpHtml as $key=>$item){
    if($key==0){
        continue;
    }
    preg_match('/^(.*?)<\/td>/',$item,$m);
    $volTitle=$m[1];
    preg_match_all('/<td class="ccss"><a href="(.*?)">(.*?)<\/a>/',$item,$m);
    $arr=[];
    foreach ($m[1] as $k=>$v){
        if($m[2][$k]=='插图')continue;
        $arr[$m[2][$k]]=$chaptersPageBaseUrl.$v;
    }
    $chapterUrls[$volTitle]=$arr;
}


if(file_exists($storyId)){
    delDirAndFile($storyId);
    echo '检测到此书本存在，已经删除旧的文件' . PHP_EOL;
}
mkdir($storyId);
$img_name=$storyId.'/img';
mkdir($img_name);
try{
    preg_match('/<img src="(.*?)" border="0" width="168" align="center" hspace="5" vspace="0" \/>/',$bookHtml,$m);
    $coverUrl=$m[1];
    file_put_contents($storyId.'/cover.jpg',file_get_contents($coverUrl));
}catch(Exception $e){
    echo '无法抓取封面' . PHP_EOL;
}
file_put_contents($storyId.'/SUMMARY.md','# 目录'.PHP_EOL.'* [说明](README.md)'.	PHP_EOL);
file_put_contents($storyId.'/README.md','# 说明'.PHP_EOL.'本电子书使用[wenku8爬虫](https://github.com/juzeon/advanced-php-crawler)创建，祝你阅读愉快！'.PHP_EOL);
echo PHP_EOL.PHP_EOL;

$count=0;
foreach($chapterUrls as $volTitle=>$vol) {
    $f=fopen($storyId.'/SUMMARY.md','a');
    fwrite($f,'* ['.$volTitle.'](SUMMARY.md)'.PHP_EOL);
    fclose($f);
    foreach ($vol as $chapterTitle=>$chapterUrl){
        echo '#现在处理：' . $chapterTitle.' - '.$volTitle.' '.$chapterUrl . PHP_EOL;
        for ($i = 0; $i < 5; $i++) {
            $chapterHtml=strToUtf8(file_get_contents($chapterUrl));
            preg_match('/<\/ul>([\s\S]*?)<ul id/',$chapterHtml,$m);
            $content=$m[1];
            $content=str_replace('&nbsp;&nbsp;&nbsp;&nbsp;','',$content);
            $content=str_replace('<br />',PHP_EOL.PHP_EOL,$content);
            if(empty(trim($content))){
                if($i==4){
                    echo '##ERROR：正则匹配失败，且超出5次重试次数，请打开网页检查问题。' . PHP_EOL . '---------' . PHP_EOL;
                    break;
                }
                echo '##ERROR：正则匹配失败！重试次数'.($i+1) . PHP_EOL;
                sleep(2);
                continue;
            }else{
                echo '##内容有效' . PHP_EOL;
            }
            $content=strip_tags($content);
            $f=fopen($storyId.'/'.$count.'.md','w');
            fwrite($f,'# '.$chapterTitle.PHP_EOL.'### 序号：'.$count.PHP_EOL.'### 字数：'.number_format(mb_strlen($content)).PHP_EOL.$content.PHP_EOL);
            fclose($f);
            $f=fopen($storyId.'/SUMMARY.md','a');
            fwrite($f,'   * ['.$chapterTitle.' - '.$volTitle.']('.$count.'.md)'.PHP_EOL);
            fclose($f);
            echo '#写入数据完毕' . PHP_EOL . '---------' . PHP_EOL;
            $count++;
            break;
        }
    }
}
echo '=============='.PHP_EOL.'#开始处理书本依赖文件'.PHP_EOL;
$json_book=array(
    'title'=>$storyName,
    'description'=>'wenku8爬虫于'.date('Y年m月d日 H:i').'生成的电子书',
    'language'=>'zh-cn',
    'author'=>'wenku8爬虫'
);
$book_json=json_encode($json_book);
file_put_contents($storyId.'/book.json',$book_json);
echo '#全部任务处理完毕' . PHP_EOL . '---------' . PHP_EOL;
echo '#你可以编辑book.json来修改这本书的详细信息，然后——'.PHP_EOL;
echo '#使用gitbook工具输出为mobi格式电子书：gitbook mobi '.$storyId.'/ '.$storyId.'.mobi'.PHP_EOL;
echo '#使用gitbook工具输出为epub格式电子书：gitbook epub '.$storyId.'/ '.$storyId.'.epub'.PHP_EOL;
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
function strToUtf8 ($str = '') {
    $current_encode = mb_detect_encoding($str, array("ASCII","GB2312","GBK",'BIG5','UTF-8'));
    $encoded_str = mb_convert_encoding($str, 'UTF-8', $current_encode);
    return $encoded_str;
}