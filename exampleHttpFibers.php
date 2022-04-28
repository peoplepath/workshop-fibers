<?php

class Http {
    private const CONNECTION_TIMEOUT = 50;
    private const DATA_TIMEOUT = 1;

    public string $url;
    private string $host;
    private string $port;
    private string $path;

    public function __construct(string $url) {
        $this->url = $url;

        $components = parse_url($url);
        $this->host = $components['host'];
        $this->port = $components['port'] ?? "80";
        $this->path = $components['path'] ?? "/";
    }

    /**
     * Begins fetching of the data
     */
    public function fetch() : Fiber {
        return new Fiber(function() {
            $socket = stream_socket_client(
                sprintf("%s://%s:%s", "tcp", $this->host, $this->port),
                $errorNumber,
                $errorString,
                null,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );
            stream_set_blocking($socket, false);

            $getBody  = sprintf("GET %s HTTP/1.1\r\n", $this->path);
            $getBody .= sprintf("Host: %s\r\n", $this->host);
            $getBody .= sprintf("Accept: */*\r\n");
            $getBody .= "\r\n";
            $startTime = time();
            $streamEnded = false;
            $reads = [];
            $writes = [$socket];
            $excepts = [];
            $socketsAvailable = stream_select($reads, $writes, $excepts, self::DATA_TIMEOUT);

            // Wait for the stream to be writable
            while ($socketsAvailable === 0) {
                Fiber::suspend();
                $socketsAvailable = stream_select($reads, $writes, $excepts, self::DATA_TIMEOUT);
            }

            fwrite($socket, $getBody);

            $buffer = "";
            while (time() - $startTime < self::CONNECTION_TIMEOUT) {
                // echo "read ".$this->url."\n";
                $reads = [$socket];
                $writes = [];
                $excepts = [];
                stream_select($reads, $writes, $excepts, self::DATA_TIMEOUT);
                $data = fread($socket, 512);
                if ($data !== false) {
                    // fread will be blank upon initial connecting/decrypting
                    if ($data === "" && $buffer !== "") {
                        $streamEnded = true;
                        break;
                    } elseif ($data === "" && $buffer === "") {
                        // Just keep waiting
                        Fiber::suspend();
                    } elseif ($data !== ""){
                        // Read the data
                        $buffer .= $data;
                        Fiber::suspend();
                    }
                }
            }

            if (!$streamEnded) {
                throw new Exception("Connection timed out.");
            }

            $parsed = explode("\r\n\r\n", $buffer);
            return ['headers' => $parsed[0], 'body' => $parsed[1]];
        });
    }
}

class Loop {
    public static array $activeAwaits = [];

    public static function await(Fiber $childFiber) : mixed {
        self::$activeAwaits[] = Fiber::getCurrent();
        $childFiber->start();
        while ($childFiber->isTerminated() === false) {
            $childFiber->resume();
            if ($childFiber->isTerminated()){
                break;
            }

            Fiber::suspend();
        }

        return $childFiber->getReturn();
    }

    public static function run() : void {
        while (count(self::$activeAwaits) > 0) {
            foreach(self::$activeAwaits as $index => $parentFiber) {
                if ($parentFiber->isSuspended() && $parentFiber->isTerminated() === false){
                    $parentFiber->resume();
                } elseif ($parentFiber->isTerminated()){
                    unset(self::$activeAwaits[$index]);
                }
            }
        }
    }
}

$requests = [
    new Http("http://jsonplaceholder.typicode.com/comments"),
    new Http("http://jsonplaceholder.typicode.com/posts/2"),
    new Http("http://jsonplaceholder.typicode.com/posts/3"),
];

$start = microtime(true);
foreach($requests as $request){
    $childFiber = new Fiber(function() use ($request){
        $response = Loop::await($request->fetch());

        echo "\n".$request->url."\n";
        var_dump($response['body']);
    });

    $childFiber->start();
}

Loop::run();
echo sprintf("\n Elapsed: %f sec\n",(microtime(true) - $start));
