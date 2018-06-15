<?php
require_once("include/db_info.inc.php");
header('Content-type: application/json');
foreach ($dbh->query("SELECT * FROM weibo_hot_searchs where `created_at` >= CURRENT_TIMESTAMP - INTERVAL 10 MINUTE") as $row) {
    echo $row['json'];
    return;
}
$curl = curl_init();
$data = array();

curl_setopt_array($curl, array(
    CURLOPT_URL => "https://s.weibo.com/top/summary",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache"
    ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
    echo "cURL Error #:" . $err;
} else {
    require_once("include/db_info.inc.php");
    $pattern = "/(?<=STK.pageletM.view\()[\s\S]*?}(?=\))/";
    preg_match_all($pattern, $response, $matches);
    for ($i = 0; $i < sizeof($matches[0]); $i++) {
        if (json_decode($matches[0][$i])->{'pid'} == "pl_top_realtimehot") {
            break;
        }
    }

    $json = json_decode($matches[0][$i]);
    $html = $json->{'html'};
    preg_match_all("/<a.*?>[\s\S]*?<\/a>/", $html, $a_tags);
    $a_tags = $a_tags[0];
    for ($i = 0, $j = 0; $j < 3 && $i < sizeof($a_tags); $i++) {
        if (preg_match("/(?<=>).+(?=<\/a)/", $a_tags[$i], $title)) {
            preg_match("/((?<=href=\").*?(?=\")|(?<=href=').*?(?='))/", $a_tags[$i], $href);
//            $data = array($data, array($j + 1, $title[0], $href[0]));
            array_push($data, array("num" => $j + 1, "title" => $title[0], "href" => $href[0]));
            $j++;
        }
    }
    $data = json_encode($data, JSON_UNESCAPED_UNICODE);
//    echo "INSERT INTO `weibo_hot_searchs`(json) values ($data)" . "\n";
    $dbh->query("INSERT INTO weibo_hot_searchs(json) values ('$data')");
    echo $data;
}

