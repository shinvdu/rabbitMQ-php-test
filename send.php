<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();


$channel->queue_declare('hello', false, false, false, false);
$rand = rand();
$msg = new AMQPMessage('Hello World!'.$rand);
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!' $rand \n";

$channel->close();
$connection->close();

?>

