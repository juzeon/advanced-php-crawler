# advanced-php-crawler
## 新浪博客全能爬虫
这套PHP编写的小程序可以帮助喜欢在电子书阅读器上看新浪博客上文章的你，它可以根据已知的文章列表来爬行，亦可以根据已知的文章目录来爬行——你只需要将URL写入一个文本文件，接着调用程序即可。当然，你可以使用`#`符号作为注释，与bash类似。而且，它生成的是gitbook的标准格式，可以用gitbook/calibre工具自动生成多种格式（mobi/epub/pdf）的电子书。文章细节均已自动优化，爬行图片保存到本地，也生成封面，且使用MarkDown格式，只为带给你完美的阅读体验！
## 简明教程
淀粉月刊撰写的本程序简明教程：[https://dfkan.com/1635.html](https://dfkan.com/1635.html)
## 文件功能详解：

需要PHP5以上版本，Windows用户可安装[phpstudy](http://www.phpstudy.net/)。

### sina-list.php
它用于爬行像这样子的博客文章目录：
![][image-1]

>  新浪博客爬虫-列表爬虫
> 
> -可以集合已知文章目录（/s/articlelist*）里面的文章列表
> 
> 使用方法：
> 
> php sina-list.php \<网址文件\>
> 
> 参数解释：
> 
> \<网址文件\>：一行一个网址，请使用电脑版访问后复制
> 
> 命令示例：
> 
> php sina-list.php urls.txt
> 
> 网址文件示例：
> 
> http://blog.sina.com.cn/s/articlelist_123456wsla.html #我是注释
> 
> http://blog.sina.com.cn/s/articlelist_789456wsex.html 
> 

### sina-article.php
它用于爬行像这样子的具体文章：
![][image-2]

> 新浪博客爬虫-文章爬虫
> 
> -可以提取已知文章页面（/s/blog*）里面的文章
> 
> 使用方法：
> 
> php sina-article.php \<网址文件\>
> 
> 参数解释：
> 
> \<网址文件\>：一行一个网址，请使用电脑版访问后复制
> 
> 命令示例：
> 
> php sina-article.php urls.txt
> 
> 网址文件示例：
> 
> http://blog.sina.com.cn/s/blog_123456wsla.html
> 
> http://blog.sina.com.cn/s/blog_789456wsex.html
> 

### tool-rev.php
它用于把上面说的网址文件前前后后颠倒过来
> 新浪博客爬虫-网址文件反转工具
> 
> -将某个网址文件里面的url全部反转过来，可用于处理新旧文章顺序等
> 
> 使用方法：
> php tool-rev.php \<网址文件\>
> 
> 参数解释：
> 
> \<网址文件\>：一行一个网址，请使用电脑版访问后复制
> 
> 命令示例：
> 
> php tool-rev.php urls.txt
> 
> 网址文件示例：
> 
> http://blog.sina.com.cn/s/xxx_123456wsla.html
> 
> http://blog.sina.com.cn/s/xxx_789456wsex.html
> 

## 附录
[Gitbook安装全解](http://www.jianshu.com/p/7476afdd9248)

[Gitbook+Calibre安装及使用](https://kindlefere.com/post/288.html#gb_6)

[1]:	https://kindlefere.com/post/82.html

[image-1]:	https://ww2.sinaimg.cn/large/006tNbRwgy1fdizlqnd8qj30s40i30wu.jpg
[image-2]:	https://ww1.sinaimg.cn/large/006tNbRwgy1fdizoxf1ivj30i40m7tgl.jpg