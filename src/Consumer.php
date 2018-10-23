<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Exception\RequestException;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * This class shows how you can use signals to handle consumers
 */
class Consumer
{
    //address for testing
    public $address = 'https://putsreq.com/0JvSjtbgvvtLLebCdjD4';

    /**
     * Setup signals and connection
     *
     * @var \PhpAmqpLib\Connection\AMQPConnection $connect
     * @var \Monolog\Logger $logger
     * @var \GuzzleHttp\Client $sender
     */
    public function __construct($connect, $logger, $sender)
    {
        $this->connection = $connect;
        $this->log = $logger;
        $this->sender = $sender;

        if (extension_loaded('pcntl')) {
            define('AMQP_WITHOUT_SIGNALS', false);
            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
            pcntl_signal(SIGHUP, [$this, 'signalHandler']);
            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
            pcntl_signal(SIGALRM, [$this, 'alarmHandler']);
        } else {
            echo 'Unable to process signals.' . PHP_EOL;
            exit(1);
        }
    }

    /**
     * Signal handler
     *
     * @param  int $signalNumber
     * @return void
     */
    public function signalHandler($signalNumber)
    {
        echo 'Handling signal: #' . $signalNumber . PHP_EOL;
        global $consumer;
        switch ($signalNumber) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
                $consumer->stopHard();
                break;
            case SIGINT:   // 2  : ctrl+c
                $consumer->stop();
                break;
            case SIGHUP:   // 1  : kill -s HUP
                $consumer->restart();
                break;
            case SIGUSR1:  // 10 : kill -s USR1
                // send an alarm in 1 second
                pcntl_alarm(1);
                break;
            case SIGUSR2:  // 12 : kill -s USR2
                // send an alarm in 10 seconds
                pcntl_alarm(10);
                break;
            default:
                break;
        }
        return;
    }

    /**
     * Alarm handler
     *
     * @param  int $signalNumber
     * @return void
     */
    public function alarmHandler($signalNumber)
    {
        echo 'Handling alarm: #' . $signalNumber . PHP_EOL;
        echo memory_get_usage(true) . PHP_EOL;
        return;
    }

    /**
     * Message handler
     *
     * @param  \PhpAmqpLib\Message\AMQPMessage $message
     * @return void
     */
    public function messageHandler(AMQPMessage $message)
    {
        echo "\n--------\n";
        echo $message->body;
        echo "\n--------\n";

        $this->log->addInfo('Received message: ' . $message->body);
        try {
            $this->sender->request(
                'POST', $this->address, [
                    'body' => $message->body,
                ]
            );
            $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            $this->log->addInfo('Massage sent:');
        } catch (RequestException $e) {
            $this->log->addInfo('message didn\'t sent: ');

            if ($e->hasResponse()) {
                //
                $this->log->addInfo('message with response: '. $e->hasResponse());
            }
        }

        $this->log->addInfo('Memory usage: ' . memory_get_usage(true));

        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
    }

    /**
     * Start a consumer on an existing connection
     *
     * @return void
     */
    public function start()
    {
        echo 'Starting consumer.' . PHP_EOL;
        $exchange = 'router';
        $queue = 'msgs';
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($queue, false, true, false, false);
        $this->channel->exchange_declare($exchange, 'direct', false, true, false);
        $this->channel->queue_bind($queue, $exchange);
        $this->channel->basic_consume(
            $queue,
            $this->consumerTag,
            false,
            false,
            false,
            false,
            [$this, 'messageHandler'],
            null,
            ['x-cancel-on-ha-failover' => ['t', true]] // fail over to another node
        );
        echo 'Enter wait.' . PHP_EOL;

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        echo 'Exit wait.' . PHP_EOL;
    }

    /**
     * Restart the consumer on an existing connection
     */
    public function restart()
    {
        echo 'Restarting consumer.' . PHP_EOL;
        $this->stopSoft();
        $this->start();
    }

    /**
     * Close the connection to the server
     */
    public function stopHard()
    {
        echo 'Stopping consumer by closing connection.' . PHP_EOL;
        $this->connection->close();
    }

    /**
     * Close the channel to the server
     */
    public function stopSoft()
    {
        echo 'Stopping consumer by closing channel.' . PHP_EOL;
        $this->channel->close();
    }

    /**
     * Tell the server you are going to stop consuming
     * It will finish up the last message and not send you any more
     */
    public function stop()
    {
        echo 'Stopping consumer by cancel command.' . PHP_EOL;
        // this gets stuck and will not exit without the last two parameters set
        $this->channel->basic_cancel($this->consumerTag, false, true);
    }

    /**
     * Current connection
     *
     * @var \PhpAmqpLib\Connection\AMQPSSLConnection
     */
    protected $connection = null;
    /**
     * Current channel
     *
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel = null;
    /**
     * Consumer tag
     *
     * @var string
     */
    protected $consumerTag = 'consumer';

    protected $log;

    protected $sender;
}