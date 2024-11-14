<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$discord_token = $_ENV['PHAP_DISCORD_TOKEN'];
$owner_id = $_ENV['PHAP_OWNER_ID'];

$discord = new Discord([
  'token' => $discord_token,
  'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES
]);

$discord->on('ready', function ($discord) {
  echo "phap", PHP_EOL;

  $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    if (preg_match('/(f[ ]*a[ ]*p[ ]*)/i', $message->content)) {
      $message->reply('u mean ' . str_replace('@','',preg_replace('/(f)([ ]*a[ ]*p[ ]*)/i', 'ph${2}', strtolower($message->content))));
    }

    if (preg_match('/(hawk[ ]*tuah)/i', $message->content)) {
      $message->reply('spit on that thang');
    }

    if ($message->author->id === "480415224164253707" && mt_rand(1, 10000) <= 50) {
      $message->channel->sendMessage('eleofant');
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
