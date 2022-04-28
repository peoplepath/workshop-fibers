<?php

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Loop;

require __DIR__ . '/vendor/autoload.php';

$start = microtime(true);
Loop::run(static function () use ($argv): \Generator {
    $uris = [
        "http://jsonplaceholder.typicode.com/comments",
        "http://jsonplaceholder.typicode.com/posts/2",
        "http://jsonplaceholder.typicode.com/posts/3",
    ];

    // Instantiate the HTTP client
    $client = HttpClientBuilder::buildDefault();

    $requestHandler = static function (string $uri) use ($client): \Generator {
        /** @var Response $response */
        $response = yield $client->request(new Request($uri));

        return yield $response->getBody()->buffer();
    };

    try {
        $promises = [];

        foreach ($uris as $uri) {
            $promises[$uri] = Amp\call($requestHandler, $uri);
        }

        $bodies = yield $promises;

        foreach ($bodies as $uri => $body) {
            var_dump($body);
        }
    } catch (HttpException $error) {
        // If something goes wrong Amp will throw the exception where the promise was yielded.
        // The HttpClient::request() method itself will never throw directly, but returns a promise.
        echo $error;
    }
});

echo sprintf("\n Elapsed: %f sec\n",(microtime(true) - $start));
