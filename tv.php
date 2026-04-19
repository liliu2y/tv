<?php
header('Content-Type: text/html; charset=utf-8');

/**
 * 获取客户端真实IP
 * @return string 客户端IP地址
 */
function getClientIp() {
    $ip = '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim($ip);
}

/**
 * 将IP地址转换为整数，便于比较
 * @param string $ip IP地址
 * @return int IP对应的整数
 */
function ipToInt($ip) {
    return ip2long($ip);
}

/**
 * 从TXT数据库中查询IP信息
 * @param string $ip 要查询的IP
 * @param string $dbFile IP数据库文件路径
 * @return array 包含地区和运营商的数组
 */
function queryIpFromTxt($ip, $dbFile = 'ipdata.txt') {
    // 检查数据库文件是否存在
    if (!file_exists($dbFile) || !is_readable($dbFile)) {
        throw new Exception("IP数据库文件不存在或无法读取: {$dbFile}");
    }

    $ipInt = ipToInt($ip);
    $handle = fopen($dbFile, 'r');
    if (!$handle) {
        throw new Exception("无法打开IP数据库文件: {$dbFile}");
    }

    $result = [
        'region' => '', // 地区信息（国家-省-市-区县）
        'isp' => ''     // 运营商
    ];

    // 逐行读取并匹配IP段
    while (($line = fgets($handle)) !== false) {
        // 去除空白字符和换行符
        $line = trim($line);
        if (empty($line)) continue;

        // 按空白字符分割行（支持多个空格或制表符）
        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 4) continue; // 跳过格式不正确的行

        list($startIp, $endIp, $region, $isp) = $parts;

        // 转换IP段为整数
        $startInt = ipToInt($startIp);
        $endInt = ipToInt($endIp);

        // 判断当前IP是否在该网段内
        if ($ipInt >= $startInt && $ipInt <= $endInt) {
            $result['region'] = $region;
            $result['isp'] = $isp;
            break; // 找到匹配项即退出
        }
    }

    fclose($handle);
    return $result;
}

/**
 * 根据IP信息获取对应的下载链接
 * @param array $ipInfo IP查询结果
 * @return string 下载链接
 */
function getDownloadUrl($ipInfo) {
    $region = $ipInfo['region'];
    $isp = $ipInfo['isp'];

    // 判断是否为天津地区（地区格式如：中国–天津–天津–南开区）
    if (strpos($region, '–天津–') !== false) {
        switch (true) {
            case strpos($isp, '联通') !== false:
                return 'https://gh.dpik.top/https://github.com/liliu2y/tv/blob/main/tjcu.txt';
            case strpos($isp, '移动') !== false:
                return 'https://gh.dpik.top/https://github.com/liliu2y/tv/blob/main/tjcm.txt';
            case strpos($isp, '电信') !== false:
                return 'https://gh.dpik.top/https://github.com/liliu2y/tv/blob/main/tjct.txt';
            default:
                return 'https://gh.dpik.top/https://github.com/kakaxi-1/IPTV/blob/main/iptv.txt';
        }
    } else {
        return 'https://gh.dpik.top/https://github.com/kakaxi-1/IPTV/blob/main/iptv.txt';
    }
}

/**
 * 跳转到下载链接
 * @param string $url 下载链接
 */
function redirectToDownload($url) {
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: {$url}");
        exit;
    } else {
        die("无效的下载链接");
    }
}

// 主流程
try {
    $clientIp = getClientIp();
    $ipInfo = queryIpFromTxt($clientIp);
    $downloadUrl = getDownloadUrl($ipInfo);
    redirectToDownload($downloadUrl);
} catch (Exception $e) {
    die("错误: " . $e->getMessage());
}

?>

