<?php

$popcorn = new Fiber(function(): void {
    echo "Putting popcorn inside microwave\n";
    Fiber::suspend(); // Microwave is preparing popcorn
    echo "Getting popcorn from microwave\n";
});

echo "Playing border game\n";
$popcorn->start();
echo "Taken control back...\n";
echo "Playing border game\n";
echo "Resuming Fiber...\n";
$popcorn->resume();
echo "Playing border game\n";
echo "Go to web\n";

