    <?php  
      
    /* 
     * Created on 2012-12-21 
     * Created by RobinTang 
     * To change the template for this generated file go to 
     * Window - Preferences - PHPeclipse - PHP - Code Templates 
     */  
    class SinCookie {  
        public $name; // Cookie名称  
        public $value; // Cookie值  
      
        // 下面三个属性现在未实现  
        public $expires; // 过期时间  
        public $path; // 路径  
        public $domain; // 域  
      
        // 从Cookie字符串创建一个Cookie对象  
        function __construct($s = false) {  
            if ($s) {  
                $i1 = strpos($s, '=');  
                $i2 = strpos($s, ';');  
                $this->name = trim(substr($s, 0, $i1));  
                $this->value = trim(substr($s, $i1 +1, $i2 - $i1 -1));  
            }  
        }  
        // 获取Cookie键值对  
        function getKeyValue() {  
            return "$this->name=$this->value";  
        }  
    }  
      
    // 会话上下文  
    class SinHttpContext {  
        public $cookies; // 会话Cookies  
        public $referer; // 前一个页面地址  
      
        function __construct() {  
            $this->cookies = array ();  
            $this->refrer = "";  
        }  
      
        // 设置Cookie  
        function cookie($key, $val) {  
            $ck = new SinCookie();  
            $ck->name = $key;  
            $ck->value = $val;  
            $this->addCookie($ck);  
        }  
        // 添加Cookie  
        function addCookie($ck) {  
            $this->cookies[$ck->name] = $ck;  
        }  
        // 获取Cookies字串，请求时用到  
        function cookiesString() {  
            $res = '';  
            foreach ($this->cookies as $ck) {  
                $res .= $ck->getKeyValue() . ';';  
            }  
            return $res;  
        }  
    }  
      
    // Http请求对象  
    class SinHttpRequest {  
        public $url; // 请求地址  
        public $method = 'GET'; // 请求方法  
        public $host; // 主机  
        public $path; // 路径  
        public $scheme; // 协议，http  
        public $port; // 端口  
      
        public $header; // 请求头  
        public $body; // 请求正文  
      
        // 设置头  
        function setHeader($k, $v) {  
            if (!isset ($this->header)) {  
                $this->header = array ();  
            }  
            $this->header[$k] = $v;  
        }  
      
        // 获取请求字符串  
        // 包含头和请求正文  
        // 获取之后直接写socket就行  
        function reqString() {  
            $matches = parse_url($this->url);  
            !isset ($matches['host']) && $matches['host'] = '';  
            !isset ($matches['path']) && $matches['path'] = '';  
            !isset ($matches['query']) && $matches['query'] = '';  
            !isset ($matches['port']) && $matches['port'] = '';  
      
            $host = $matches['host'];  
            $path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';  
            $port = !empty ($matches['port']) ? $matches['port'] : 80;  
            $scheme = $matches['scheme'] ? $matches['scheme'] : 'http';  
      
            $this->host = $host;  
            $this->path = $path;  
            $this->scheme = $scheme;  
            $this->port = $port;  
      
            $method = strtoupper($this->method);  
            $res = "$method $path HTTP/1.1\r\n";  
            $res .= "Host: $host\r\n";  
      
            if ($this->header) {  
                reset($this->header);  
                while (list ($k, $v) = each($this->header)) {  
                    if (isset ($v) && strlen($v) > 0)  
                        $res .= "$k: $v\r\n";  
                }  
            }  
            $res .= "\r\n";  
            if ($this->body) {  
                $res .= $this->body;  
                $res .= "\r\n\r\n";  
            }  
            return $res;  
        }  
    }  
      
    // Http响应  
    class SinHttpResponse {  
        public $scheme; // 协议  
        public $stasus; // 状态，成功的时候是ok  
        public $code; // 状态码，成功的时候是200  
        public $header; // 响应头  
        public $body; // 响应正文  
        function __construct() {  
            $this->header = array ();  
            $this->body = null;  
        }  
        function setHeader($key, $val) {  
            $this->header[$key] = $val;  
        }  
    }  
      
    // HttpClient  
    class SinHttpClient {  
        public $keepcontext = true; // 是否维持会话  
        public $context; // 上下文  
        public $request; // 请求  
        public $response; // 响应  
      
        public $debug = false; // 是否在Debug模式，为true的时候会打印出请求内容和相同的头部  
      
        function __construct() {  
            $this->request = new SinHttpRequest();  
            $this->response = new SinHttpResponse();  
            $this->context = new SinHttpContext();  
            $this->timeout = 15; // 默认的超时为15s  
        }  
      
        // 清除上一次的请求内容  
        function clearRequest() {  
            $this->request->body = '';  
            $this->request->setHeader('Content-Length', false);  
            $this->request->setHeader('Content-Type', false);  
        }  
        // post方法  
        // data为请求的数据  
        // 为键值对的时候模拟表单提交  
        // 其他时候为数据提交，提交的形式为xml  
        // 如有其他需求，请自行扩展  
        function post($url, $data = false) {  
            $this->clearRequest();  
            if ($data) {  
                if (is_array($data)) {  
                    $con = http_build_query($data);  
                    $this->request->setHeader('Content-Type', 'application/x-www-form-urlencoded');  
                } else {  
                    $con = $data;  
                    $this->request->setHeader('Content-Type', 'text/xml; charset=utf-8');  
                }  
                $this->request->body = $con;  
                $this->request->method = "POST";  
                $this->request->setHeader('Content-Length', strlen($con));  
            }  
            $this->startRequest($url);  
        }  
        // get方法  
        function get($url) {  
            $this->clearRequest();  
            $this->request->method = "GET";  
            $this->startRequest($url);  
        }  
        //  该方法为内部调用方法，不用直接调用  
        function startRequest($url) {  
            $this->request->url = $url;  
            if ($this->keepcontext) {  
                // 如果保存上下文的话设置相关信息  
                $this->request->setHeader('Referer', $this->context->refrer);  
                $cks = $this->context->cookiesString();  
                if (strlen($cks) > 0)  
                    $this->request->setHeader('Cookie', $cks);  
            }  
            // 获取请求内容  
            $reqstring = $this->request->reqString();  
            if ($this->debug)  
                echo "Request:\n$reqstring\n";  
            try {  
                $fp = fsockopen($this->request->host, $this->request->port, $errno, $errstr, $this->timeout);  
            } catch (Exception $ex) {  
                echo $ex->getMessage();  
                exit (0);  
            }  
            if ($fp) {  
                stream_set_blocking($fp, true);  
                stream_set_timeout($fp, $this->timeout);  
                // 写数据  
                fwrite($fp, $reqstring);  
                $status = stream_get_meta_data($fp);  
                if (!$status['timed_out']) { //未超时  
                    // 下面的循环用来读取响应头部  
                    while (!feof($fp)) {  
                        $h = fgets($fp);  
                        if ($this->debug)  
                            echo $h;  
                        if ($h && ($h == "\r\n" || $h == "\n"))  
                            break;  
                        $pos = strpos($h, ':');  
                        if ($pos) {  
                            $k = strtolower(trim(substr($h, 0, $pos)));  
                            $v = trim(substr($h, $pos +1));  
      
                            if ($k == 'set-cookie') {  
                                // 更新Cookie  
                                if ($this->keepcontext) {  
                                    $this->context->addCookie(new SinCookie($v));  
                                }  
                            } else {  
                                // 添加到头里面去  
                                $this->response->setHeader($k, $v);  
                            }  
                        } else {  
                            // 第一行数据  
                            // 解析响应状态  
                            $preg = '/^(\S*) (\S*) (.*)$/';  
                            preg_match_all($preg, $h, $arr);  
                            isset ($arr[1][0]) & $this->response->scheme = trim($arr[1][0]);  
                            isset ($arr[2][0]) & $this->response->stasus = trim($arr[2][0]);  
                            isset ($arr[3][0]) & $this->response->code = trim($arr[3][0]);  
                        }  
                    }  
                    // 获取响应正文长度  
                    $len = (int) $this->response->header['content-length'];  
                    $res = '';  
                    // 下面的循环读取正文  
                    while (!feof($fp) && $len > 0) {  
                        $c = fread($fp, $len);  
                        $res .= $c;  
                        $len -= strlen($c);  
                    }  
                    $this->response->body = $res;  
                }  
                // 关闭Socket  
                fclose($fp);  
                // 把返回保存到上下文维持中  
                $this->context->refrer = $url;  
            }  
        }  
    }  
    ?>  