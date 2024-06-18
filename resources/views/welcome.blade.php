<?php

use Basis\Nats\Client;
use Basis\Nats\Configuration;

// this is default options, you can override anyone
$configuration = new Configuration([
    'host' => 'localhost',
    'jwt' => null,
    'lang' => 'php',
    'pass' => null,
    'pedantic' => false,
    'port' => 4222,
    'reconnect' => true,
    'timeout' => 1,
    'token' => null,
    'user' => null,
    'nkey' => null,
    'verbose' => false,
    'version' => 'dev',
]);

// default delay mode is constant - first retry be in 1ms, second in 1ms, third in 1ms
$configuration->setDelay(0.001);

// linear delay mode - first retry be in 1ms, second in 2ms, third in 3ms, fourth in 4ms, etc...
$configuration->setDelay(0.001, Configuration::DELAY_LINEAR);

// exponential delay mode - first retry be in 10ms, second in 100ms, third in 1s, fourth if 10 seconds, etc...
$configuration->setDelay(0.01, Configuration::DELAY_EXPONENTIAL);

$client = new Client($configuration);
$client->ping(); // true

// queue usage example
$queue = $client->subscribe('test_subject');

$client->publish('test_subject', 'hello');
$client->publish('test_subject', 'world');

// optional message fetch
// if there are no updates null will be returned
$message1 = $queue->fetch();
echo $message1->payload . PHP_EOL; // hello
echo '<br>';

// locks untill message is fetched from subject
// to limit lock timeout, pass optional timeout value
$message2 = $queue->next();
echo $message2->payload . PHP_EOL; // world
echo '<br>';

$client->publish('test_subject', 'hello');
$client->publish('test_subject', 'batching');

// batch message fetching, limit argument is optional
$messages = $queue->fetchAll(10);
echo count($messages);
echo '<br>';

// fetch all messages that are published to the subject client connection
// queue will stop message fetching when another subscription receives a message
// in advance you can time limit batch fetching
$queue->setTimeout(1); // limit to 1 second
$messages = $queue->fetchAll();

// reset subscription
$client->unsubscribe($queue);

// callback hell example
$client->subscribe('hello', function ($message) {
    var_dump('<br>got message<br>', $message); // tester
    echo '<br>';
});

$client->publish('hello', 'tester');
$client->process();
echo '<br>';

// if you want to append some headers, construct payload manually
use Basis\Nats\Message\Payload;
echo '<br>';
$payload = new Payload('tester', [
    'Nats-Msg-Id' => 'payload-example',
]);
echo '<br>';
$client->publish('hello', $payload);
$client->process();
