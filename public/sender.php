<?php
require_once '../vendor/autoload.php';

$config = file_get_contents("https://artstage.ir/mangene_config");
// assuming response is something like this:
$master_config = json_decode($config);
$peer_config = (array) $master_config->peer_to_peer_drivers;
$token = $master_config->token;


// header('Content-type: text/plain; charset=utf-8');
// include_once("check_push.php");

$debug_mode = false;

if(isset($_REQUEST["url"])){
    $req_url = url_enc($_REQUEST["url"]);
    if (filter_var($req_url, FILTER_VALIDATE_URL) === FALSE) {
        // echo $req_url . "<br>";
        echo '{
            "mg_error": "متاسفانه URL اشتباهی رو وارد کردید.",
            "log_info": "URL: '. $req_url .'",
        }';
        die();
    }
    $req_url = urldecode($_REQUEST["url"]);
}else{
    echo '{"mg_error": "متاسفانه آدرسی وارد نکرده اید."}';
}

$domain_name = explode('.', trim(str_replace(['www.'], null, parse_url($req_url)['host'])))[0];
$domain_prefix = $domain_name."_";

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $req_url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',

  CURLOPT_SSL_VERIFYHOST => 0,
  CURLOPT_SSL_VERIFYPEER => 0,
));

$response_url = curl_exec($curl);
$response_url_httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$response_url_curl_info = curl_getinfo($curl);
if($curl_error = curl_error($curl)){
    $req_count = 0;
    do{
        $response_url = curl_exec($curl);
        $req_count++;
    }while( !curl_error($curl) || $req_count < 2);
}

if($curl_error = curl_error($curl)){
    echo "Application Can't Retrive Data from URL: <br>";
    if(isset($_REQUEST["url"])){
        echo "the Req URL: " . $_REQUEST["url"] . "<br>";
    }
    // echo "the URL: " . $req_url . "<br>";
    echo "<pre>";
    echo "<br> -Curl Error-: <br>";
    var_dump($curl_error);
    echo "<br> -Curl Info-: <br>";
    var_dump($curl_info);
    echo "</pre>";
    die();
}

if($response_url_httpcode == "404"){
    die("404 The Page Not Found");
}

curl_close($curl);

/* Use internal libxml errors -- turn on in production, off for debugging  - or use @ before $dom*/
// libxml_use_internal_errors(true);
$dom = new DomDocument;
@$dom->loadHTML($response_url);
// @$dom->loadHTMLFile($response_url);
// @$dom->loadHTMLFile($req_url);
$xpath = new DomXPath($dom);

// Post Title
$title = $xpath->query("//meta[@property = 'og:title']/@content");
$title = trim($title[0]->nodeValue);

// Post Lead
$lead = $xpath->query("//meta[@property = 'og:description']/@content");
if(trim($lead[0]->nodeValue) != ""){
    $lead = trim($lead[0]->nodeValue);
}else{
    $lead = $title;
}

// Post Image URL
$img_url = $xpath->query("//meta[@property = 'og:image']/@content");
$img_url = trim($img_url[0]->nodeValue);

if($img_url == ""){
    $img_url = "default/{$domain_prefix}default_media.jpg";
}


// article or video.other
$page_type = $xpath->query("//meta[@property = 'og:type']/@content");
$page_type = trim($page_type[0]->nodeValue);

if ($page_type == 'video.other') {
    $masterImageNode = $xpath->query($query = "//div[contains(@class, 'primary-files')]//div[contains(@class, 'contain-img')]/@href");
    if ($masterImageNode->length > 0) {
        // Loop through the found href attributes and print them
        foreach ($masterImageNode as $hrefNode) {
            $img_url = $hrefNode->nodeValue;
        }
    } else {
        $img_url = "default/{$domain_prefix}default_media.jpg";
    }
}

// checking Title & Lead Length
if(mb_strlen($title) > 75){
    $title = mb_substr($title , 0 , 72);
    $title = $title."...";
}

if(mb_strlen($lead) > 150){
    $lead = mb_substr($lead , 0 , 147);
    $lead = $lead . "...";
}

// echo $title;
// echo "<br>";
// echo $lead;
// echo "<br>";
// echo $img_url;
// echo "<br>";
// echo "<br>";
// echo mb_strlen($title);
// die();


// Getting Data fron Rokna NEWS
$post_title = $title;
$post_des = $lead;
$post_url = $req_url;
$post_img = $img_url;
$post_img = preg_replace("/ /", "%20", $img_url);


// Crop Media Image to 480x240(px)
// crop_img($post_img , "/temp/cropped_media.jpg" , 480 , 240);
// Crop Icon Image to 480x240(px)
// crop_img($post_img , "/temp/cropped_icon.jpg" , 120 , 120);

if ($post_img == "default/{$domain_prefix}default_media.jpg") {

    $cropped_image = $post_img;
    $cropped_icon = "default/{$domain_prefix}default_icon.jpg";

} else {

    $api_res = file_get_contents("https://artstage.ir/gd_api?img=$post_img");
    $master_res = json_decode($api_res);

    $cropped_image = $master_res->img;
    $cropped_icon = $master_res->icon;
}

file_put_contents("cropped_media.jpg", file_get_contents($cropped_image));
file_put_contents("cropped_icon.jpg", file_get_contents($cropped_icon));

// CURL Parameters to push the sanjagh item
$url = "https://back.sanjagh.com/api/panel/ads";

// Data Fields
$fields = [
    "campaign_id" => "5ef302f806aa0d0221085583",
    "title" => $post_title,
    "platform" => "web",
    "ad_format" => "push_notification",
    "url" => $post_url,
    "budget_type" => "cpm",
    "cost" => "4500",
    "description" => $post_des,
    "speed" => "10",
    // "media_ids" => "5e47eb4b310a2a511e1d1862",

    // UTMs
    "utm_type" => "2",
    "utm_source" => "sanjagh",
    "utm_medium" => "push_notification",
    "utm_campaign" => "campaign_id",
    "utm_campaign_type" => "1",
    "utm_term" => "ad_id",
    "utm_term_type" => "2",
    "utm_content" => "media_name",
    "utm_content_type" => "0"
];

$fields2 = [
    "campaign_id" => "6187c024f87e6e71802c1382",
    "title" => $post_title,
    "platform" => "web",
    "ad_format" => "push_notification",
    "url" => $post_url,
    "budget_type" => "cpm",
    "cost" => "4500",
    "description" => $post_des,
    "speed" => "10",
    // "media_ids" => "5e47eb4b310a2a511e1d1862",

    // UTMs
    "utm_type" => "2",
    "utm_source" => "sanjagh",
    "utm_medium" => "push_notification",
    "utm_campaign" => "campaign_id",
    "utm_campaign_type" => "1",
    "utm_term" => "ad_id",
    "utm_term_type" => "2",
    "utm_content" => "media_name",
    "utm_content_type" => "0"
];

$fields3 = [
    "campaign_id" => "6186471af9916649360fbc42",
    "title" => $post_title,
    "platform" => "web",
    "ad_format" => "push_notification",
    "url" => $post_url,
    "budget_type" => "cpm",
    "cost" => "4500",
    "description" => $post_des,
    "speed" => "10",
    // "media_ids" => "5e47eb4b310a2a511e1d1862",

    // UTMs
    "utm_type" => "2",
    "utm_source" => "sanjagh",
    "utm_medium" => "push_notification",
    "utm_campaign" => "campaign_id",
    "utm_campaign_type" => "1",
    "utm_term" => "ad_id",
    "utm_term_type" => "2",
    "utm_content" => "media_name",
    "utm_content_type" => "0"
];

$fields4 = [
    "campaign_id" => "63d6541169559c04b80723a3",
    "title" => $post_title,
    "platform" => "web",
    "ad_format" => "push_notification",
    "url" => $post_url,
    "budget_type" => "cpm",
    "cost" => "4500",
    "description" => $post_des,
    "speed" => "10",
    // "media_ids" => "5e47eb4b310a2a511e1d1862",

    // UTMs
    "utm_type" => "2",
    "utm_source" => "sanjagh",
    "utm_medium" => "push_notification",
    "utm_campaign" => "campaign_id",
    "utm_campaign_type" => "1",
    "utm_term" => "ad_id",
    "utm_term_type" => "2",
    "utm_content" => "media_name",
    "utm_content_type" => "0"
];


// Files to Upload
// Check for no img posts
if($post_img == "default/{$domain_prefix}default_media.jpg"){
    $filenames = [
        "icon" => "default/{$domain_prefix}default_icon.jpg",
        "media" => "default/{$domain_prefix}default_media.jpg"
    ];
}else{
    $filenames = [
        "icon" => 'cropped_icon.jpg',
        "media" => 'cropped_media.jpg'
    ];
}

$files = array();
foreach ($filenames as $n =>$f){
   $files[$n] = file_get_contents($f);
}


$boundary = uniqid();
$delimiter = '-------------' . $boundary;

$post_data = build_data_files($boundary , $fields , $files);

$headers = [
    "Host: back.sanjagh.com",
    "Accept: application/json, text/plain, */*",
    "Accept-Language: en-US,en;q=0.5",
    "Authorization: Bearer $token",
    "Content-Type: multipart/form-data; boundary=" . $delimiter,
    "Content-Length: " . strlen($post_data),
    "Origin: https://panel.sanjagh.com",
    "Connection: keep-alive",
    "Referer: https://panel.sanjagh.com/ads/new",
    "Pragma: no-cache",
    "Cache-Control: no-cache"
];


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);

//CURL SSL Disabling
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);

curl_setopt($ch, CURLOPT_FOLLOWLOCATION , 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1);

// CURL HEADERS
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// CURL POST Methode data sending
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

// CURL HTTP Version 1 for sending Multipart Files to server
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

// CURL Rokna PUSH
$response = curl_exec($ch);
$rk_response_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_info = curl_getinfo($ch);
if($curl_error = curl_error($ch)){
    $req_count = 0;
    do{
        $response_url = curl_exec($ch);
        $req_count++;
    }while( curl_error($ch) || $req_count < 1);
}
if($curl_error = curl_error($ch)){
    echo "Rokna PUSH Failed, Error: <br>";
    var_dump($curl_error);
    echo "Rokna PUSH Failed, Curl Info: <br>";
    var_dump($curl_info);
}

// echo "<pre>";
// var_dump($response);
// echo "<br>";
// var_dump($rk_response_httpcode);
// echo "<br>";
// var_dump($curl_info);
// echo "<br>";
// var_dump($post_data);

// CURL CinemaBartar PUSH
if (array_search('cinemabartar', $peer_config[$domain_name]) !== false) {
    $post_data2 = build_data_files($boundary , $fields2 , $files);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data2);

    $response2 = curl_exec($ch);
    $cb_response_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_info2 = curl_getinfo($ch);

    if($curl_error = curl_error($ch)){
        $req_count = 0;
        do{
            $response_url = curl_exec($ch);
            $req_count++;
        }while( curl_error($ch) || $req_count < 1);
    }
    if($curl_error = curl_error($ch)){
        echo "Cinemabartar PUSH Failed, Error: <br>";
        var_dump($curl_error);
        echo "Cinemabartar PUSH Failed, Curl Info: <br>";
        var_dump($curl_info2);
    }
}

// CURL KhateSalamat PUSH
if (array_search('khatesalamat', $peer_config[$domain_name]) !== false) {
    $post_data4 = build_data_files($boundary , $fields4 , $files);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data4);

    $response4 = curl_exec($ch);
    $cb_response_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_info4 = curl_getinfo($ch);

    if($curl_error = curl_error($ch)){
        $req_count = 0;
        do{
            $response_url = curl_exec($ch);
            $req_count++;
        }while( curl_error($ch) || $req_count < 1);
    }
    if($curl_error = curl_error($ch)){
        echo "Khatesalamat PUSH Failed, Error: <br>";
        var_dump($curl_error);
        echo "Khatesalamat PUSH Failed, Curl Info: <br>";
        var_dump($curl_info2);
    }
}


// CURL Shockonline PUSH
$post_data3 = build_data_files($boundary , $fields3 , $files);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data3);

$response3 = curl_exec($ch);
$so_response_httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_info3 = curl_getinfo($ch);

if($curl_error = curl_error($ch)){
    $req_count = 0;
    do{
        $response_url = curl_exec($ch);
        $req_count++;
    }while( curl_error($ch) || $req_count < 1);
}

if($curl_error = curl_error($ch)){
    echo "Shock PUSH Failed, Error: <br>";
    var_dump($curl_error);
    echo "Shock PUSH Failed, Curl Info: <br>";
    var_dump($curl_info3);
}


// $a = strpos($response , "code");
// $b = substr($response , $a + 6 , 3);

$json_start_pos = strpos($response , "{");
$res_json = json_decode(substr($response , $json_start_pos));


if($res_json->code == "200"){
    echo '{"rk_push": 1}';
}else{
    echo '{"rk_push": 0}';
}


echo "response:";
echo "<br>";
var_dump($response);
echo "</pre>";

curl_close($ch);

if(file_exists("destination.txt") || file_exists("cropped_icon.jpg") || file_exists("cropped_media.jpg") || file_exists("post_image.jpg")){
    clear_temp();
}

//$ch = curl_init();
//curl_setopt($ch, CURLOPT_URL, "/notif/".base64_encode($post_title));
//curl_setopt($ch, CURLOPT_HEADER, 0);
//curl_exec($ch);
//curl_close($ch);

// clear Files
function clear_temp(){
    @unlink("destination.txt");
    @unlink("cropped_icon.jpg");
    @unlink("cropped_media.jpg");
    @unlink("post_image.jpg");
}
// Cropper Function to prepare images
function crop_img($main_image , $final_image_name , $final_width , $final_height){

    if(!isset($main_image)){
        return "give a img";
    }
    if(!isset($final_width)){
        return "give a width";
    }
    if(!isset($final_height)){
        return "give a height";
    }

    $ext = pathinfo(basename($main_image), PATHINFO_EXTENSION);
    // copy ($main_image, 'temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);
    copy ($main_image, 'temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);

    $img_path = 'temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext;
    if(strpos($img_path , ".jpg") !== false){
        $org_image = imagecreatefromjpeg('temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);
    }elseif(strpos($img_path , ".webp") !== false){
        $org_image = imagecreatefromwebp('temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);
    }elseif(strpos($img_path , ".png") !== false){
        $org_image = imagecreatefrompng('temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);
    }else{
        $org_image = imagecreatefromstring('temp'.DIRECTORY_SEPARATOR.'post_image.'.$ext);
    }

    $width = imagesx($org_image);
    $height = imagesy($org_image);

    $orginal_aspect = $width / $height;
    $final_aspect = $final_width / $final_height;

    if($orginal_aspect >= $final_aspect){
        $new_height = $final_height;
        // $new_width = $width / ($height / $final_height);
        $new_width = intval($width / ($height / $final_height));
    }else{
        $new_width = $final_width;
        // $new_height = $height / ($width / $final_width);
        $new_height = intval($height / ($width / $final_width));
    }

    $final_image = imagecreatetruecolor($final_width , $final_height);

    @imagecopyresampled(
        $final_image,
        $org_image,
        0 - ($new_width - $final_width) / 2,
        0 - ($new_height - $final_height) / 2,
        0,
        0,
        $new_width, $new_height,
        $width, $height
    );
    if(imagejpeg($final_image, $final_image_name, 80)){
        return true;
    }
}
function build_data_files($boundary, $fields, $files){
    $data = '';
    $eol = "\r\n";
    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
            . $content . $eol;
    }
    foreach ($files as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
            //. 'Content-Type: image/png'.$eol
            . 'Content-Transfer-Encoding: binary'.$eol
            ;

        $data .= $eol;
        $data .= $content . $eol;
    }
    $data .= "--" . $delimiter . "--".$eol;
    return $data;
}
function url_enc($url){
    $url = urldecode($url);

    // if($parted_url = parse_url($url)){
    //     $url = $parted_url["scheme"] . "//" . $parted_url["host"] . urlencode($parted_url["path"]);
    // }

    if(stripos($url , "https://") !== null || stripos($url , "http://") !== null){
        $slash_pos = stripos($url , "/" , 9);
        if($slash_pos){
            $url_route = substr($url , $slash_pos);
            $domain = substr($url , 0 , $slash_pos + 1);
            $url = $domain . urlencode($url_route);
        }
    }else{
        $slash_pos = stripos($url , "/" , 2);
        if($slash_pos){
            $url_route = substr($url , $slash_pos);
            $domain = substr($url , 0 , $slash_pos + 1);
            $url = $domain . urlencode($url_route);
        }
    }

    return $url;
}
function video_check($url){
    $url = urldecode($url);
    if(strpos($url , 'video') !== false || strpos($url , "/n/") ){
        return true;
    }else{
        return false;
    }
}
function get_data($req_url){
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => $req_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',

    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    ));

    $response_url = curl_exec($curl);
    $response_url_httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $response_url_curl_info = curl_getinfo($curl);

    if($curl_error = curl_error($curl)){
        $req_count = 0;
        do{
            $response_url = curl_exec($curl);
            $req_count++;
        }while( !curl_error($curl) || $req_count < 2);
    }

    if($curl_error = curl_error($curl)){
        echo "Application Can't Retrive Data from URL: <br>";
        if(isset($_REQUEST["url"])){
            echo "the Req URL: " . $_REQUEST["url"] . "<br>";
        }
        // echo "the URL: " . $req_url . "<br>";
        echo "<pre>";
        echo "<br> -Curl Error-: <br>";
        var_dump($curl_error);
        echo "<br> -Curl Info-: <br>";
        var_dump($response_url_curl_info);
        echo "</pre>";
        die();
    }

    if($response_url_httpcode == "404"){
        die("404 The Page Not Found");
    }

    return $response_url;

    curl_close($curl);
}
