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

function recursiveReplacement(string $message): string {
  while(preg_match('/(f[ ]*a[ ]*p[ ]*)/', $message)) {
    $message = preg_replace('/(f)([ ]*a[ ]*p[ ]*)/', 'ph${2}', $message);
  }
  return $message;
}

function filterMention(string $message): string {
  return str_replace('@', '', $message);
}

$discord->on('ready', function ($discord) {
  echo "phap", PHP_EOL;

  $discord->on('message', function ($message, $discord) {
    
    if (preg_match('/(f[ ]*a[ ]*p[ ]*)/', $message->content)) {
      $message->reply('u mean ' . recursiveReplacement(filterMention($message->content)));
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
