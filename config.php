<?php
require_once 'environment.php';

global $config;
$config = array();

if(ENVIRONMENT == 'development'){
    define('HOME', 'http://des.devstagram.net');
    $config['dbname']               =   'imagestagram';
    $config['host']                 =   'localhost';
    $config['dbuser']               =   'root';
    $config['dbpass']               =   'root';
    $config['jwt_secret_key']       =   'abC123!';
}else{
    define('HOME', 'http://des.devstagram.net');
    $config['dbname']               =   'imagestagram';
    $config['host']                 =   'localhost';
    $config['dbuser']               =   'root';
    $config['dbpass']               =   'root';
    $config['jwt_secret_key']       =   'abC123!';
}

try{
    global $pdo;
    $pdo = new PDO("mysql:dbname=".$config['dbname'].";host=".$config['host'], $config['dbuser'], $config['dbpass']);
}catch (Throwable $e){
    echo "Erro de Conexão: ".$e->getMessage();
}