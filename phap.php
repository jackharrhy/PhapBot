<?php

include __DIR__ . '/vendor/autoload.php';

use Discord\Discord;
use Discord\Parts\User\Member;
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

$db = new SQLite3('phap.db');

$db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS guild_config(
    guild_id TEXT PRIMARY KEY,
    guild_touch_role TEXT NOT NULL
)
SQL);

$db->exec(<<<SQL
CREATE TABLE IF NOT EXISTS touch_log(
    message_id TEXT PRIMARY KEY,
    start_time INTEGER NOT NULL,
    end_time INTEGER DEFAULT NULL,
    duration INTEGER DEFAULT NULL,
    ended_by_message_id TEXT REFERENCES touch_log(message_id) DEFAULT NULL,
    guild_id TEXT NOT NULL REFERENCES guild_config(guild_id) ON DELETE CASCADE,
    user_id TEXT NOT NULL,
    message_content TEXT NOT NULL
)
SQL);

function set_guild_config($guild_id, $guild_touch_role): void
{
  global $db;
  $stm = $db->prepare(<<<SQL
  INSERT OR REPLACE INTO guild_config(
    guild_id,
    guild_touch_role
  ) VALUES(?, ?)
  SQL);
  $stm->bindParam(1, $guild_id);
  $stm->bindParam(2, $guild_touch_role);
  $stm->execute();
}

function get_guild_touch_role($guild_id): string|false
{
  global $db;
  $stm = $db->prepare(<<<SQL
  SELECT guild_touch_role FROM guild_config
  WHERE guild_id = ?
  SQL);
  $stm->bindParam(1, $guild_id);
  $res = $stm->execute();
  $array = $res->fetchArray();
  if ($array) {
    return reset($array);
  }
  return false;
}

function get_last_touch_log($guild_id): array|false
{
  global $db;
  $stm = $db->prepare(<<<SQL
  SELECT message_id, start_time, user_id
  FROM touch_log
  WHERE guild_id = ?
  ORDER BY end_time ASC
  LIMIT 1
  SQL);
  $stm->bindParam(1, $guild_id);
  $res = $stm->execute();
  return $res->fetchArray();
}

function end_touch_log($message, $last_touch_log): void
{
  print "END TOUCH LOG\n";
  $message_id = $last_touch_log[0];
  $start_time = $last_touch_log[1];
  $end_time = time();
  $duration = $end_time - $start_time;

  global $db;
  $stm = $db->prepare(<<<SQL
  UPDATE touch_log SET
    end_time = ?,
    duration = ?,
    ended_by_message_id = ?
  WHERE message_id = ?
  SQL);
  $stm->bindParam(1, $end_time);
  $stm->bindParam(2, $duration);
  $ended_by_message_id = $message->id;
  $stm->bindParam(3, $ended_by_message_id);
  $stm->bindParam(4, $message_id);
  $stm->execute();
}

function start_touch_log($message): void
{
  print "START TOUCH LOG\n";
  global $db;
  $stm = $db->prepare(<<<SQL
  INSERT INTO touch_log(
    message_id,
    start_time,
    guild_id,
    user_id,
    message_content
  ) VALUES(?, ?, ?, ?, ?)
  SQL);
  $message_id = $message['id'];
  $stm->bindParam(1, $message_id);
  $time = time();
  $stm->bindParam(2, $time, SQLITE3_INTEGER);
  $guild_id = $message->guild_id;
  $stm->bindParam(3, $guild_id);
  $user_id = $message['user_id'];
  $stm->bindParam(4, $user_id);
  $content = $message['content'];
  $stm->bindParam(5, $content);
  $stm->execute();
}

function apply_touch(Message $message, Discord $discord, string $guild_touch_role): void
{
  print "APPLY TOUCH\n";
  $guild_id = $message->guild_id;
  $last_touch_log = get_last_touch_log($guild_id);

  if ($last_touch_log !== false) {
    $user_id = $last_touch_log[2];

    if ($message->user_id == $user_id) {
      print("ALREADY TOUCH\n");
      // already have applied the touch
      return;
    }

    end_touch_log($message, $last_touch_log);

    $discord->guilds->fetch($user_id)->done(function (Member $member) use ($guild_touch_role) {
      $member->removeRole($guild_touch_role)->done(function () {
        print("REMOVED TOUCH ROLE!\n");
      });
    });
  }

  start_touch_log($message);

  $message->member->addRole($guild_touch_role)->done(function () {
    print("ADDED TOUCH ROLE!\n");
  });
}

$discord->on('ready', function ($discord) {
  echo "phap", PHP_EOL;

  $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
    try {
      if (preg_match('/(f[ ]*a[ ]*p[ ]*)/i', $message->content)) {
        if ($message->guild_id !== null) {
          $guild_touch_role = get_guild_touch_role($message->guild_id);

          if ($guild_touch_role) {
            apply_touch($message, $discord, $guild_touch_role);
          }
        }

        $message->reply('u mean ' . str_replace('@', '', preg_replace('/(f)([ ]*a[ ]*p[ ]*)/i', 'ph${2}', strtolower($message->content))));
      }

      if ($message->author->id === $GLOBALS['owner_id']) {
        if (strpos($message->content, 'phapbot, configure touch, with the role ') === 0) {
          $guild_touch_role = substr($message->content, strlen('phapbot, configure touch, with the role '));
          set_guild_config($message->guild_id, $guild_touch_role);
          return;
        }

        if (strpos($message->content, 'phapxecute ') === 0) {
          $command = substr($message->content, strlen('phapxecute '));
          $output = shell_exec($command . ' 2>&1');
          $message->channel->sendMessage('```' . str_replace('```', '\`\`\`', $output) . '```');
          return;
        }
      }
    } catch (Exception $e) {
      echo "Caught exception: ", $e->getMessage(), "\n";
    }
  });
});

$discord->run();
