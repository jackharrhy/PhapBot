<?php

include __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::create(__DIR__); $dotenv->load();

$owner_id = getenv('PHAP_OWNER_ID');

$discord = new \Discord\Discord([
  'token' => getenv('PHAP_DISCORD_TOKEN'),
]);

$discord->on('ready', function ($discord) {
  echo "Bot is ready.", PHP_EOL;

  $discord->on('message', function ($message) {
    if (strpos($message->content, 'fap') === 0) {
      $message->reply('u mean phap');
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
