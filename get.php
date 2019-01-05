<?php
require_once("include/db_info.inc.php");
require_once("bootstrap.php");

use voku\helper\HtmlDomParser;

header('Content-type: application/json');
if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $id = 1;
}
$url = "";
foreach ($dbh->query("SELECT * FROM weibo_hot_searchs order by `created_at` desc limit 1") as $row) {
    $url = "https://s.weibo.com" . json_decode($row['json'], true)[$id - 1]['href'];
//    echo $url . "\n\n";
//    echo json_decode($row['json'], true)[$id - 1]['href'];
    break;
}
header('Content-type: application/json');
//$html = new simple_html_dom();
$curl = curl_init();
$data = array();

curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
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
    $html = HtmlDomParser::str_get_html($response);
    $last_div = '';
    foreach ($html->find('div#pl_feedlist_index div') as $e) {
        $last_div = $e->find('div.card')[0]->find('div.content')[0];
        break;
    }
    $div_tags = $last_div->find('p.txt[node-type=feed_list_content_full]')[0];
    if (empty($div_tags)) {
        $div_tags = $last_div->find('p.txt[node-type=feed_list_content]')[0];
    }

    preg_match("/(?<=nick-name=\")[\s\S]*?(?=\")/", $div_tags, $nick_name);
    preg_match("/<p.*?>[\s\S]*?<\/p>/", $div_tags, $p_tags);
    echo json_encode(array("nick_name" => $nick_name[0], "content" => preg_replace("/<.*?>|[\s]|#/", "", $p_tags[0])));
}

