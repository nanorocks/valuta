<?php
/**
 * Composer
 */
require_once __DIR__ . './../vendor/autoload.php';

/**
 * ENV INS
 */
$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../');
$dotenv->load();

/**
 * ENV VARS
 */
$apiEndpoints = getenv('API_CONTENT');
$url = getenv('URL');


/**
 * Validate request for converter
 */
function validateConverter($to, $from, $price) : int
{
    $validator = 0;

    if(!is_numeric($price))
    {
        $validator = 1;
    }

    if(!ctype_alpha($from) || !ctype_alpha($to))
    {
        $validator = 1;
    }

    if($from == $to)
    {
        $validator = 1;
    }

    return $validator;
}



/**
 * Routes
 */
Flight::route('GET /', function() use ($apiEndpoints) {
    echo "<pre>" . $apiEndpoints . "</pre>";
    die();
});

Flight::route('GET /api/list', function() use ($url){
    $jsonResponse = file_get_contents($url);
    $response = json_decode($jsonResponse,true);
    echo Flight::json($response);
    die();
});

Flight::route('GET /api/converter/@from/@to/@price', function($from, $to, $price) use ($url){

    $response = [
        'error' => true,
        'status_text' => 'Invalid parameters',
        'status_code' => 200,
        'data' => null
    ];

    $validator = validateConverter($to, $from, $price);
    if($validator)
    {
        echo Flight::json($response);
        die();
    }

    $from = strtoupper($from);
    $to = strtoupper($to);

    $jsonResponse = file_get_contents($url);
    $rates = json_decode($jsonResponse,  JSON_UNESCAPED_UNICODE);

    foreach ($rates['data'] as $rate)
    {
        if($rate['oznaka'] == $from)
        {
            $from = $rate;
        }
        if($rate['oznaka'] == $to)
        {
            $to = $rate;
        }
    }

    $denar = 0;
    $finalPrice = 0;
    // FOR DENAR-VALUE ONLY
    if(is_string($to) && strtoupper($to) == 'MKD')
    {
        $finalPrice = (floatval($price) * floatval($from['sreden']));
        $denar = 1;
    }
    else if(is_string($from) && strtoupper($from) == 'MKD')
    {
        $finalPrice = (floatval($price) / floatval($to['sreden']));
        $denar = 1;
    }

    if (!$denar)
    {
        if(!is_array($to) || !is_array($from))
        {
            echo Flight::json($response);
            die();
        }

        $finalPrice = ( floatval($price) * floatval($from['sreden']) ) / floatval($to['sreden']);
    }

    $response['error'] = false;
    $response['status_text'] = 'OK';
    $response['status_code'] = 200;
    $response['data'] = [
      'price' => $finalPrice
    ];

    echo Flight::json($response);
    die();
});

Flight::route('GET /api/history/@value', function($value){
    echo 'hello world!' . getenv('MAIN_API_PATH');
});

Flight::start();