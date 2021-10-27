<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
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

  $discord->on('message', function ($message, $discord) {

    if (strpos($message->content, 'fap') !== false) {
      $message->reply('u mean ' . str_replace('@','',str_replace('fap', 'phap', $message->content)));
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
