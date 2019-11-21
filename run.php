<?php

include __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__); $dotenv->load();

$discord = new \Discord\Discord([
  'token' => getenv('PHAP_DISCORD_TOKEN'),
]);

$discord->on('ready', function ($discord) {
  echo "Bot is ready.", PHP_EOL;

  $discord->on('message', function ($message) {
    if (strpos($message->content, 'fap') === 0) {
      $message->reply('u mean phap');
    }
  });
});

$discord->run();
