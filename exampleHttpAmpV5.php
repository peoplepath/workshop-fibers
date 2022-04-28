
<?php

use Amp\Future;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use function Amp\async;

require __DIR__ . '/vendor/autoload.php';
$start = microtime(true);
$uris = [
    "http://jsonplaceholder.typicode.com/comments",
    "http://jsonplaceholder.typicode.com/posts/2",
    "http://jsonplaceholder.typicode.com/posts/3",
];

// Instantiate the HTTP client
$client = HttpClientBuilder::buildDefault();

$requestHandler = static function (string $uri) use ($client): string {
    $response = $client->request(new Request($uri));
    return $response->getBody()->buffer();
};

try {
    $futures = [];

    foreach ($uris as $uri) {
        $futures[$uri] = async(fn () => $requestHandler($uri));
    }

    $bodies = Future\all($futures);

    foreach ($bodies as $uri => $body) {
        echo "\n".$uri."\n";
        var_dump($body);
    }
} catch (HttpException $error) {
    // If something goes wrong Amp will throw the exception where the promise was yielded.
    // The HttpClient::request() method itself will never throw directly, but returns a promise.
    echo $error;
}

echo sprintf("\n Elapsed: %f sec\n",(microtime(true) - $start));
