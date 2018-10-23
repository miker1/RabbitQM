# RabbitQM
app.php - for starting this application
in time of execution this program you can use pclnt signals such as SIGTERM, SIGQUIT, SIGINT, SIGHUP.
for checking whether the script works properly you can use this script:

    use PhpAmqpLib\Connection\AMQPStreamConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    /**
     * @param array $credentials
     * @return string
     */

    $credentials = [
        'header' => 'POST',
        'body' => 'It is jsonText',
        'OtherProperties' =>[
            'prop1' => 1,
            'prop2' => 2
        ],
    ];
    $jsonCredentials = json_encode($credentials);

    $exchange = 'router';
    $queue = 'msgs';

    $connection = new AMQPStreamConnection(HOST, PORT, USER, PASS, VHOST);
    $channel = $connection->channel();
    /*
        The following code is the same both in the consumer and the producer.
        In this way we are sure we always have a queue to consume from and an
            exchange where to publish messages.
    */
    /*
        name: $queue
        passive: false
        durable: true // the queue will survive server restarts
        exclusive: false // the queue can be accessed in other channels
        auto_delete: false //the queue won't be deleted once the channel is closed.
    */
    $channel->queue_declare($queue, false, true, false, false);
    /*
        name: $exchange
        type: direct
        passive: false
        durable: true // the exchange will survive server restarts
        auto_delete: false //the exchange won't be deleted once the channel is closed.
    */
    $channel->exchange_declare($exchange, 'direct', false, true, false);

    $channel->queue_bind($queue, $exchange);

    $message = new AMQPMessage($jsonCredentials, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

    $channel->basic_publish($message, $exchange);

    $channel->close();
    $connection->close();


