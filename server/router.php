<?php

class Router {
    private static Router $instance;
    protected function __construct() { }
    protected function __clone() { }
    public function __wakeup() {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
    * @var string[] $server_uri - List of URI's of the server
    */
    private $server_uri = [];

    /**
    * @var callable $callback - Callback to run after URI match
    */
    private $callback;

    /**
     * @var boolean $matched - Toggle on route matched
     */
    private $matched = false;

    /**
     * @var array $params - List of dynamic URI parameters
     */
    public $params = [];

    public static function getInstance(): Router {
        if (!isset(self::$instance)) {
            self::$instance = new static();
            $uri = trim($_SERVER['PATH_INFO'], '/\^$');
            self::$instance->server_method = strtolower($_SERVER['REQUEST_METHOD']);
            self::$instance->server_uri = explode('/', $uri);
        }

        return self::$instance;
    }
    
    /**
     * add - Adds a URI for matching
     * 
     * @param string $method
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    public function add($method, $uri, $callback){
        $this->match(strtolower($method), $uri, $callback);
    }

    public function addMany($methods, $uri, $callback) {
        foreach ($methods as $m) {
            $this->match(strtolower($m), $uri, $callback);
        }
    }

    /**
     * match - Match URIs with server
     * 
     * @param string $method
     * @param string $uri
     * @param callable $callback
     * @return void
     */
    private function match($method, $uri, $callback){
        if($this->matched){
            return;
        }
        $uri = trim($uri, '/\^$');
        $current_uri = explode('/', $uri);
        $uri_length = count($current_uri);

        if($method != $this->server_method){
            return;
        }

        if($uri_length != count($this->server_uri)){
            return;
        }

        $matched = true;

        for($i = 0; $i < $uri_length; $i++){
            if($current_uri[$i] == $this->server_uri[$i]){
                continue;
            }
            if(isset($current_uri[$i][0]) && $current_uri[$i][0] == ':'){
                $this->params[substr($current_uri[$i], 1)] = $this->server_uri[$i];
                continue;
            }
            $matched = false;
            break;
        }

        if($matched){
            $this->callback = $callback;
            $this->matched = true;
        }
    }

    /**
     * listen - Run the callback function of matched route
     * @return void
     */
    public function listen(){
        if(!$this->matched){
            http_response_code(404);
            return;
        }

        call_user_func($this->callback, $this);
        die(json_encode([
            "code" => $GLOBALS["CODE"],
            "message" => $GLOBALS["MESSAGE"],
            "data" => $GLOBALS["DATA"]
        ]));
    }
}