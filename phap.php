<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$discord_token = $_ENV['PHAP_DISCORD_TOKEN'];
$owner_id = $_ENV['PHAP_OWNER_ID'];

$cowsay_animals = [];
$cowsay_output = shell_exec('cowsay -l 2>&1');

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

$insults_dir = __DIR__.'/assets/insults';
$insults = is_dir($insults_dir) ? glob($insults_dir.'/*.mp3') : [];

$bofh_excuses_file = __DIR__.'/assets/bofh_excuses.txt';
$bofh_excuses = is_file($bofh_excuses_file)
  ? array_values(array_filter(array_map('trim', file($bofh_excuses_file))))
  : [];

$discord = new Discord([
  'token' => $discord_token,
  'intents' => Intents::getDefaultIntents() | Intents::GUILD_MESSAGES | Intents::MESSAGE_CONTENT,
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

    if (preg_match('/\bi\s*n+e+e+d+\s*w+i+s+d+o+m+\b/i', $message->content)) {
      $random_animal = $GLOBALS['cowsay_animals'][array_rand($GLOBALS['cowsay_animals'])];
      $output = shell_exec("fortune | cowsay -f $random_animal 2>&1");
      if ($output) {
        $message->reply('```' . str_replace('```', '\`\`\`', $output) . '```');
      } else {
        $message->reply('```No wisdom available right now...```');
      }
    }

    if (preg_match('/\bi\s*(?:do\s*n[o\']?t|don[o\']?t)\s*n+e+e+d+\s*w+i+s+d+o+m+\b/i', $message->content)
        || preg_match('/\bi\s*n+e+e+d+\s*b+a+d+\s*w+i+s+d+o+m+\b/i', $message->content)) {
      $random_animal = $GLOBALS['cowsay_animals'][array_rand($GLOBALS['cowsay_animals'])];
      $output = shell_exec("fortune -o | cowsay -f $random_animal 2>&1");
      if ($output) {
        $message->reply('```' . str_replace('```', '\`\`\`', $output) . '```');
      } else {
        $message->reply('```No bad wisdom available right now...```');
      }
    }

    if (!empty($GLOBALS['insults']) && preg_match('/\b(?:o+o+p+s+(?:ie+s?)?|o+o+f+)\b/i', $message->content)) {
      $insult = $GLOBALS['insults'][array_rand($GLOBALS['insults'])];
      $message->channel->sendMessage(MessageBuilder::new()->addFile($insult));
    }

    if (!empty($GLOBALS['bofh_excuses']) && preg_match('/\bwhy\s+(?:is|was|are|were|did|does|do)\s+(?:\S+\s+){1,3}(?:broken|fail(?:ing|ed|s)?|busted|borked|crashing|crashed|down|dead|not\s+working)\b/i', $message->content)) {
      $excuse = $GLOBALS['bofh_excuses'][array_rand($GLOBALS['bofh_excuses'])];
      $message->reply('BOFH excuse: '.$excuse);
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
