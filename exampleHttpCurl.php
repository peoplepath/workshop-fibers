<?php

$urls = [
    "http://jsonplaceholder.typicode.com/comments",
    "http://jsonplaceholder.typicode.com/posts/2",
    "http://jsonplaceholder.typicode.com/posts/3",
];

$start = microtime(true);
foreach ($urls as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = curl_exec($ch);

    echo "\n".$url."\n";
    echo $res."\n";
}

echo sprintf("\n Elapsed: %f sec\n",(microtime(true) - $start));
