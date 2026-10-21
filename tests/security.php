<?php

require '/app/vendor/autoload.php';

if (PHP_VERSION !== '8.5.12' || !PHP_ZTS) {
    throw new RuntimeException('Expected PHP 8.5.12 ZTS');
}
printf("PHP %s ZTS; OpenSSL %s\n", PHP_VERSION, OPENSSL_VERSION_TEXT);
foreach (['openssl', 'curl', 'mbstring', 'sodium', 'pdo_sqlite'] as $extension) {
    if (!extension_loaded($extension)) {
        throw new RuntimeException("Missing extension: $extension");
    }
}
foreach ([Discord\Discord::class, Dotenv\Dotenv::class, React\Socket\Connector::class] as $class) {
    if (!class_exists($class)) {
        throw new RuntimeException("Cannot autoload $class");
    }
}
echo json_encode(openssl_get_cert_locations(), JSON_PRETTY_PRINT), "\n";

$hosts = [
    'www.php.net' => true,
    'dv-g3b-emudhra.we-have-a-problem.com' => false,
    'dv-g3b-aaa-emudhra.we-have-a-problem.com' => false,
];

foreach ($hosts as $host => $trusted) {
    // Discord HTTP and WebSocket clients use React's default TLS connector.
    $connector = new React\Socket\Connector(['timeout' => 15]);
    $passed = false;
    $connector->connect("tls://$host:443")->then(
        function ($connection) use ($host, $trusted, &$passed) {
            $connection->close();
            $passed = $trusted;
            printf("React TLS %s: accepted\n", $host);
        },
        function (Throwable $error) use ($host, $trusted, &$passed) {
            $certificateError = str_contains(strtolower($error->getMessage()), 'certificate verify failed');
            $passed = !$trusted && $certificateError;
            printf("React TLS %s: %s\n", $host, $error->getMessage());
        }
    );
    React\EventLoop\Loop::run();
    if (!$passed) {
        throw new RuntimeException("Unexpected React TLS result for $host");
    }

    $curl = curl_init("https://$host/");
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($curl);
    $errno = curl_errno($curl);
    printf("PHP cURL %s: errno=%d %s\n", $host, $errno, curl_error($curl));
    if (($trusted && $response === false) || (!$trusted && $errno !== CURLE_PEER_FAILED_VERIFICATION)) {
        throw new RuntimeException("Unexpected cURL TLS result for $host");
    }
}
echo "Runtime, autoload, and TLS verification passed; no bot was started.\n";
