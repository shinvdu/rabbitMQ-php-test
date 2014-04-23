Introduction

Where to get help
If you're having trouble going through this tutorial you can contact us through
the discussion list or directly.

RabbitMQ is a message broker. In essence, it accepts messages from producers,
and delivers them to consumers. In-between, it can route, buffer, and persist
the messages according to rules you give it.

RabbitMQ, and messaging in general, uses some jargon.

Producing means nothing more than sending. A program that sends messages is a
producer. We'll draw it like that, with "P":


A queue is the name for a mailbox. It lives inside RabbitMQ. Although messages
flow through RabbitMQ and your applications, they can be stored only inside a
queue. A queue is not bound by any limits, it can store as many messages as you
like - it's essentially an infinite buffer. Many producers can send messages
that go to one queue - many consumers can try to receive data from one queue. A
queue will be drawn like this, with its name above it:


Consuming has a similar meaning to receiving. A consumer is a program that
mostly waits to receive messages. On our drawings it's shown with "C":


Note that the producer, consumer, and broker do not have to reside on the same
machine; indeed in most applications they don't.

"Hello World"

(using the php-amqplib Client)
In this part of the tutorial we'll write two programs in PHP; a producer that
sends a single message, and a consumer that receives messages and prints them
out. We'll gloss over some of the detail in the php-amqplib API, concentrating
on this very simple thing just to get started. It's a "Hello World" of
messaging.

In the diagram below, "P" is our producer and "C" is our consumer. The box in
the middle is a queue - a message buffer that RabbitMQ keeps on behalf of the
consumer.

(P) -> [|||] -> (C)
The php-amqplib client library

RabbitMQ speaks AMQP, which is an open, general-purpose protocol for messaging.
There are a number of clients for AMQP in many different languages. We'll use
the php-amqplib in this tutorial.

Add a composer.json file to your project:

{
        "require": {
                    "videlalvaro/php-amqplib": "v2.1.0"
                        }
}
Provided you have composer installed, you can run the following:

$ composer.phar install
Now we have the php-amqplib installed, we can write some code.

Sending
(P) -> [|||]
We'll call our message sender send.php and our message receiver receive.php. The
sender will connect to RabbitMQ, send a single message, then exit.

In send.php, we need to include the library and use the necessary classes:

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
then we can create a connection to the server:

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
The connection abstracts the socket connection, and takes care of protocol
version negotiation and authentication and so on for us. Here we connect to a
broker on the local machine - hence the localhost. If we wanted to connect to a
broker on a different machine we'd simply specify its name or IP address here.

Next we create a channel, which is where most of the API for getting things done
resides.

To send, we must declare a queue for us to send to; then we can publish a
message to the queue:

$channel->queue_declare('hello', false, false, false, false);

$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!'\n";
Declaring a queue is idempotent - it will only be created if it doesn't exist
already. The message content is a byte array, so you can encode whatever you
like there.

Lastly, we close the channel and the connection;

$channel->close();
$connection->close();
Here's the whole send.php class.

Sending doesn't work!

If this is your first time using RabbitMQ and you don't see the "Sent" message
then you may be left scratching your head wondering what could be wrong. Maybe
the broker was started without enough free disk space (by default it needs at
least 1Gb free) and is therefore refusing to accept messages. Check the broker
logfile to confirm and reduce the limit if necessary. The configuration file
documentation will show you how to set disk_free_limit.

Receiving
That's it for our sender. Our receiver is pushed messages from RabbitMQ, so
unlike the sender which publishes a single message, we'll keep it running to
listen for messages and print them out.

[|||] -> (C)
The code (in receive.php) has almost the same include and uses as send:

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPConnection;
Setting up is the same as the sender; we open a connection and a channel, and
declare the queue from which we're going to consume. Note this matches up with
the queue that send publishes to.

$connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('hello', false, false, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
Note that we declare the queue here, as well. Because we might start the
receiver before the sender, we want to make sure the queue exists before we try
to consume messages from it.

We're about to tell the server to deliver us the messages from the queue. We
will define a PHP callable that will receive the messages sent by the server.
Keep in mind that messages are sent asynchronously from the server to the
clients.

$callback = function($msg) {
      echo " [x] Received ", $msg->body, "\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
        $channel->wait();
}
Our code will block while our $channel has callbacks. Whenever we receive a
message our $callback function will be passed the received message.

Here's the whole receive.php class

Putting it all together
Now we can run both scripts. In a terminal, run the sender:

$ php send.php
then, run the receiver:

$ php receive.php
The receiver will print the message it gets from the sender via RabbitMQ. The
receiver will keep running, waiting for messages (Use Ctrl-C to stop it), so try
running the sender from another terminal.

If you want to check on the queue, try using rabbitmqctl list_queues.

Hello World!

http://www.rabbitmq.com/tutorials/tutorial-one-php.html
