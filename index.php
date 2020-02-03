 <?php
//学校教务系统的编码
header("Content-type: text/html; charset=gb2312");
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
function curl_post_request($url, $header='', $data='', $cookie='', $returnCookie=0){
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
//账号登录地址
$url = "https://cas.ecit.cn/index.jsp?service=http://portal.ecit.cn/Authentication";
//1.get获取第一个cookie值
$filecontent = curl_get_request($url, '', '', 1);
$cookie_1 = $filecontent['cookie'];
//2.获取隐藏参数lt
$preg = "/<input type=\"hidden\" name=\"lt\" value=\"(.*)\" \/><\/div>/";
preg_match_all($preg, $filecontent['content'], $arr);
$lt = $arr[1][0];
//3.post获取第二个cookie值，地址不变,需要请求的参数
$data = array (
    'username' => 'your student id',
    'password' => 'your password',
    'lt' => $lt,
    'Submit' => ''
);
$filecontent = curl_post_request($url, '', $data, $cookie_1, 1);
$cookie_2 = $filecontent['cookie'];
//4.整合两个cookie值，去访问http://jw.ecit.cn/login.jsp，得到参数ticket的值
$cookie = $cookie_1.";".$cookie_2;
$url='http://jw.ecit.cn/login.jsp';
$filecontent=curl_get_request($url, '', $cookie, 0);
$preg2 = "/ticket=(.*)\";/";
preg_match_all($preg2, $filecontent, $arr2);
$ticket=$arr2[1][0];
//5.用ticket拼接url请求http://jw.ecit.cn/login.jsp
$url=$url.'?ticket='.$ticket;
curl_get_request($url, '', $cookie, 0);
//6.用ticket拼接url请求成绩页
$url='jw.ecit.cn/reportFiles/student/cj_zwcjd_all.jsp'.'?ticket='.$ticket;
//$url='http://jw.ecit.cn/gradeLnAllAction.do?type=ln&oper=qbinfo&lnxndm=2017-2018%D1%A7%C4%EA%B5%DA%D2%BB%D1%A7%C6%DA(%C1%BD%D1%A7%C6%DA)'.'&ticket='.$ticket;
$score=curl_get_request($url, '', $cookie, 0);
echo $score;//获取成绩成功！
//file_put_contents("data.txt",serialize($score));
?>