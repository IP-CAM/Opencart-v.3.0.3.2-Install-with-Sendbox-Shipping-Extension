<?php
header('Content-Type: application/json');
$request = file_get_contents('php://input');

/* if ($_SERVER['REQUEST_METHOD'] != "POST") {
    echo "You are not authorized to do it";
    die();
} */ 

echo'nah man u be';  

$server_obj = ($_SERVER);

$query = parse_str($server_obj["QUERY_STRING"]);
$get_state = $query.($_GET["state"]);
$get_code = $query.($_GET["code"]);

$new_url = $get_state . "&code=" . $get_code;
$brand_new_url = str_replace('$','&', $new_url);
//var_dump($new_url);
//var_dump($brand_new_url);

header('Location:'.$brand_new_url);
die();

//$query = parse_str($server_obj["QUERY_STRING"]);
//$get_state = $query.($_GET["state"]);
//$get_code = $query.($_GET["code"]);

//$request_data = json_decode($request, true); 

//$get_state = $_GET["state"];
//$get_code = $_GET["code"];

//$new_url = $get_state . "/code=" . $get_code;
//$brand_new_url = str_replace('#', '&', $new_url); 

//var_dump($request_data);
//var_dump($brand_new_url);


//you need to pass the url, into this location ,
//header('Location: http://localhost/shop/opencart/upload/admin/index.php?route=extension/shipping/sendbox&user_token=mLAGSXgm1B7C58HD8A1mxklclNAfSsjf&code='.$get_code.'');