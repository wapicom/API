<?php
const API_URL = 'https://warehouse-api-test.azurewebsites.net/api/outbounds';
const CLIENT_ID = '';
const HMAC_SECRET = '';

$requestData = array(
    "orderNumber" => '000124',
    "product" => array(
        "name" => 'Demo Product-1',
        "quantity" => 3
    ),
    "additionalProducts" => array(
        array(
            "name" => 'Demo Product-2',
            "quantity" => 1
        )
    ),
    "cashOnDelivery" => 107.30,
    "receiver" => array(
        "firstName" => 'test test',
        "lastName" => "",
        "phoneNumber" => '+393403000000',
        "emailAddress" => 'test@test.com', 
        "houseNumber" => "",
        "addressText" => 'some street, 11',
        "addressAdditionalInfo" => "",
        "city" => 'Barcelona',
        "country" => 'ES',
        "zipCode" => '08002'
    ),
    "comment" => 'comment text'
);
$post_fields = json_encode($requestData);
echo $post_fields;
echo "\r\n";
$signature = hash_hmac('sha1', $post_fields, HMAC_SECRET, false);
echo $signature;
echo "\r\n";
// init curl
$ch = curl_init(API_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json ',
    'x-client-id: ' . CLIENT_ID,
    'x-signature: ' . $signature)
);
// for debug purposes
$fp = fopen(dirname(__FILE__).'\\log.txt', 'w');
curl_setopt($ch, CURLOPT_VERBOSE, 1);
curl_setopt($ch, CURLOPT_STDERR, $fp);
// send request
$data = curl_exec($ch);
curl_error($ch);
curl_close($ch);
// output response
echo 'response: ';
var_dump($data);
// close log file
fclose($fp);
