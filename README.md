### 前言
之前看到很多人做过用模拟登录教务系统来获取成绩课表等信息，比如微信小程序“We重邮”和“在武大”。感觉挺有趣的，自己也想尝试一下。但是搜索了很多相关资料后，实现起来却困难重重。在逐步仔细的分析教务系统的登录过程后，终于成功的用php curl模拟登录并获取到了成绩信息。

### 封装了两个函数
封装了两个函数来分别进行get和post请求。
```
/**
 * GET请求方法
 * @param string $url 请求的url
 * @param string $header 请求头数据
 * @param string $cookie 请求cookies
 * @param string $returnCookie 是否返回cookie
 * @return string $returnCookie==0时，返回请求返回内容
 * @return array $returnCookie==1时，返回请求返回内容content和cookie
 */
function curl_get_request($url, $header='', $cookie='', $returnCookie=0){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
    if($header) {
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);  //设置表头
    }
    if($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    $filecontent = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if($returnCookie){
        list($header, $body) = explode("\r\n\r\n", $filecontent, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/i", $filecontent, $matches);
        $cookie='';
        foreach ($matches[1] as $value) {
            $cookie = $value.';'.$cookie;
        }
        $info['cookie']  = preg_replace('# #','',$cookie);
        $info['content'] = $body;
        return $info;
    }else{
        return $filecontent;
    }
}
```

```
/**
 * POST请求方法
 * @param string $url 请求的url
 * @param string $header 请求头数据
 * @param string $data post数据
 * @param string $cookie 请求cookies
 * @param string $returnCookie 是否返回cookie
 * @return string $returnCookie==0时，返回请求返回内容
 * @return array $returnCookie==1时，返回请求返回内容content和cookie
 */
public static function curl_post_request($url, $header='', $data='', $cookie='', $returnCookie=0){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
    if($header) {
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);  //设置表头
    }
    if($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    $filecontent = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if($returnCookie){
        list($header, $body) = explode("\r\n\r\n", $filecontent, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/i", $filecontent, $matches);
        $cookie='';
        foreach ($matches[1] as $value) {
            $cookie = $value.';'.$cookie;
        }
        $info['cookie']  = preg_replace('# #','',$cookie);
        $info['content'] = $body;
        return $info;
    }else{
        return $filecontent;
    }
}
```

### 分析教务系统的登录过程

东华理工教务系统：[https://cas.ecit.cn/index.jsp?service=http://portal.ecit.cn/Authentication](https://cas.ecit.cn/index.jsp?service=http://portal.ecit.cn/Authentication)

首先当然是抓包啦，比如说用抓包工具Fiddler等，我在这里用的是浏览器自带的功能。打开教务系统，按F12，找到Network，然后进行正常登录，这时你就能看到用什么方式提交什么请求头和什么数据到哪个URL
![抓包](http://images.atecut.cn/phpcurl1.png)
![抓包](http://images.atecut.cn/phpcurl2.png)
![抓包](http://images.atecut.cn/phpcurl3.png)
如图，提交数据
```
username:1111
password:1111
lt:LT-1251522-buZk7VXiXuFcjG0Vmljj
Submit:
```
到
```
https://cas.ecit.cn/index.jsp?service=http://portal.ecit.cn/Authentication
```
我们只要模拟把这些数据提交到这个URL，就能实现模拟登录！

等等，那里有个叫`lt`的数据，而且每次刷新网页这个值都会变，怎么办！

看看源代码，果然表单里有个隐藏的input
![隐藏的参数lt](http://images.atecut.cn/phpcurl5.png)

 首先我们要知道，如果登录成功后，那么服务器会生成随机串（就是`SessionId`）来表示登录成功的状态，并返回给浏览器，浏览器得到这个串之后，作为`cookies`保存在浏览器，每次要获取登陆后里面的数据时都会提交这个串来验证是否已经登录。
自然，相同的`cookies`也有相同的`lt`。

有了思路就来写代码了，用get方式请求页面，再用正则匹配出lt：
```
$url = "https://cas.ecit.cn/index.jsp?service=http://portal.ecit.cn/Authentication";
//1.get获取第一个cookie值
$filecontent = curl_get_request($url, '', '', 1);
$cookie_1 = $filecontent['cookie'];
//2.获取隐藏参数lt
$preg = "/<input type=\"hidden\" name=\"lt\" value=\"(.*)\" \/><\/div>/";
preg_match_all($preg, $filecontent['content'], $arr);
$lt = $arr[1][0];
```
输出`$lt`试试
![隐藏的lt参数](http://images.atecut.cn/phpcurl4.png)

下一步就是用post方法提交数据了
```
//3.post获取第二个cookie值，地址不变,需要请求的参数
$data = array (
    'username' => '学号',
    'password' => '密码',
    'lt' => $lt,
    'Submit' => ''
);
$filecontent = curl_post_request($url, '', $data, $cookie_1, 1);
$cookie_2 = $filecontent['cookie'];
//4.整合两个cookie值，去访问http://jw.ecit.cn/login.jsp，得到参数ticket的值
$cookie = $cookie_1.";".$cookie_2;
```
这里为什么我要拼接两次或得到cookie呢？
这是因为我发现正常登录后会保存两个cookie值，而这两个cookie值刚好分别是get请求登录页和post请求登录页的cookie值！
![cookies](http://images.atecut.cn/phpcurl6.png)

现在我们已经获取到了cookies，说明已经登录成功了！
在登录成功后，网页会自动跳转到`ttp://jw.ecit.cn/login.jsp`这个页面，那我们get请求一下这个页面试试：
果然登录成功了！
![登录成功](http://images.atecut.cn/phpcurl7.png)
但是，看域名，这明显是经过了重定向的页面，我们看看网页源代码：
![网页源代码](http://images.atecut.cn/phpcurl9.png)
果然是一个JS重定向。接下来，我尝试了去get请求其他的登录后才能查看的页面，如成绩页，课表页，结果都会被重定向。要知道，我们得到的页面只有这样一个JS重定向的语句，没有得到任何有用的信息。

仔细看看发现每次重定向都会加个ticket参数去进行重定向，而且我发现，成功登录后这个ticket值是不会改变的。如果用一个非法的或者已过期的ticket参数去get请求页面，将会得到这样的500 Servlet Exception错误页面：
![500 Servlet Exception错误页面](http://images.atecut.cn/phpcurl10.png)

这说明，每次成功登录后，除了两个cookie外，服务器还会分配一个ticket值给客户端，用来验证是否登录成功。那这又涉及到刚刚提到的知识了。
用get方法去请求刚刚那个页面，用正则匹配出ticket值。
```
$url='http://jw.ecit.cn/login.jsp';
$filecontent=curl_get_request($url, '', $cookie, 0);
$preg2 = "/ticket=(.*)\";/";
preg_match_all($preg2, $filecontent, $arr2);
$ticket=$arr2[1][0];
```
输出一下看看：![ticket参数](http://images.atecut.cn/phpcurl11.png)

既然ticket值得到了，而且如果没ticket参数的话会重定向到`http://xxx?ticket=xxx`这样的url，那我们拼接成这样的url，再get请求试试：
```
//5.用ticket拼接url请求http://jw.ecit.cn/login.jsp
$url=$url.'?ticket='.$ticket;
echo curl_get_request($url, '', $cookie, 0);
```
看看网页源代码![获取成绩页成功！](http://images.atecut.cn/phpcurl13.png)
没问题，获取成功，不会再重定向了。

再去get请求成绩页试试
```
//6.用ticket拼接url请求成绩页
$url='http://jw.ecit.cn/gradeLnAllAction.do?type=ln&oper=qbinfo&lnxndm=2017-2018%D1%A7%C4%EA%B5%DA%D2%BB%D1%A7%C6%DA(%C1%BD%D1%A7%C6%DA)'.'&ticket='.$ticket;
$score=curl_get_request($url, '', $cookie, 0);
echo $score;//获取成绩成功！
```
![获取成绩页成功！](http://images.atecut.cn/phpcurl12.png)
OJBK！获取成绩页成功！

接下来要做的就是用正则去把一个个成绩信息匹配出来了。
不过我发现一个打印页，这个页面更好进行正则匹配，嘿嘿。
```
$url='jw.ecit.cn/reportFiles/student/cj_zwcjd_all.jsp'.'?ticket='.$ticket;
```
![获取成绩打印页页成功](http://images.atecut.cn/phpcurl17.png)

哈哈哈，高兴！

### 结尾
最后做个登录页来测试一下
![登录页](http://images.atecut.cn/phpcurl15.png)
![成绩信息页](http://images.atecut.cn/phpcurl16.png)
