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

$cowsay_output = shell_exec('cowsay -l 2>&1');
$cowsay_animals = [];

if ($cowsay_output) {
  if (preg_match('/Cow files in .*:\s*(.*)/s', $cowsay_output, $matches)) {
    $animals_string = trim($matches[1]);
    $cowsay_animals = preg_split('/\s+/', $animals_string);
    $cowsay_animals = array_filter($cowsay_animals);
  }
}

if (empty($cowsay_animals)) {
  $cowsay_animals = ['cow', 'tux', 'koala', 'dragon', 'elephant', 'sheep'];
}

$discord = new Discord([
  'token' => $discord_token,
  'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES
]);

$discord->on('ready', function ($discord) {
  echo "phap", PHP_EOL;

  $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) use ($cowsay_animals) {
    if (preg_match('/(f[ ]*a[ ]*p[ ]*)/i', $message->content)) {
      $message->reply('u mean ' . str_replace('@','',preg_replace('/(f)([ ]*a[ ]*p[ ]*)/i', 'ph${2}', strtolower($message->content))));
    }

    if (preg_match('/(hawk[ ]*tuah)/i', $message->content)) {
      $message->reply('spit on that thang');
    }

    if (preg_match('/\bi\s*n+e+e+d+\s*w+i+s+d+o+m+\b/i', $message->content)) {
      $random_animal = $cowsay_animals[array_rand($cowsay_animals)];
      $output = shell_exec("fortune | cowsay -f $random_animal 2>&1");
      if ($output) {
        $message->reply('```' . str_replace('```', '\`\`\`', $output) . '```');
      } else {
        $message->reply('```No wisdom available right now...```');
      }
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
