<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$discord_token = $_ENV['PHAP_DISCORD_TOKEN'];
$owner_id = $_ENV['PHAP_OWNER_ID'];

$discord = new Discord([
  'token' => $discord_token,
]);

$discord->on('ready', function ($discord) {
  echo "phap", PHP_EOL;

  $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    if (preg_match('/(f[ ]*a[ ]*p[ ]*)/', $message->content)) {
      $message->reply('u mean ' . str_replace('@','',preg_replace('/(f)([ ]*a[ ]*p[ ]*)/', 'ph${2}', $message->content)));
    }

    if ($message->author->id === $GLOBALS['owner_id']) {
      if (strpos($message->content, 'phapxecute ') === 0) {
        $command = substr($message->content, strlen('phapxecute '));
        $output = shell_exec($command.' 2>&1');
        $message->channel->sendMessage('```'.str_replace('```', '\`\`\`', $output).'```');
      }
    }
  });
});

$discord->run();
