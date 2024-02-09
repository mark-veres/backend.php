<?php
// This file contains all the backend code.
// DO NOT MODIFY THE CONTENTS OF THIS
// FILE UNLESS YOU KNOW WHAT YOU'RE DOING.

require_once "./sql.php";
require_once "./router.php";
require_once "./config.php";

$GLOBALS["CODE"] = 200;
$GLOBALS["MESSAGE"] = "";
$GLOBALS["DATA"] = NULL;

// The client-server communication will
// only happen through JSON objects.
header('Content-Type: application/json');

// Connect to the database
\SQL\DB::setDSN($GLOBALS["config_database_dsn"]);
\SQL\DB::setUsername($GLOBALS["config_database_username"]);
\SQL\DB::setPassword($GLOBALS["config_database_password"]);

// Declare default complex types
class File {
    public $file_name, $content;
    
    public static function getByName($file_name) {

    }
}

\SQL\Types::add(
    "File", "VARCHAR(250)",
    function ($file) {
        return $file->fileName;
    },
    function ($value) {
        return \File::getByName($value);
    }
);

$router = \Router::getInstance();

$router->addMany(["post", "get"], "/collections/:model/:action", function ($router) {
    $model_name = strtolower($router->params["model"]);
    $action_name = strtolower($router->params["action"]);
    $is_get = $_SERVER["REQUEST_METHOD"] == "GET";
    $data = $is_get ? $_GET : json_decode(file_get_contents("php://input"), true);

    foreach ($GLOBALS["config_models"] as $m) {
        if (strtolower($m) != $model_name) continue;

        $obj = new $m;
        foreach ($data as $key => $value) {
            $obj->{$key} = $value;
        }

        if ($action_name == "create") {
            $obj->create();
        } elseif ($action_name == "fetch") {
            $obj->fetch();
            $GLOBALS["DATA"] = $obj;
        } elseif ($action_name == "fetch-all") {
            $GLOBALS["DATA"] = $obj->fetchAll();
        } elseif ($action_name == "update") {
            $obj->update();
        } elseif ($action_name == "delete") {
            $obj->delete();
        }
    }
});

$router->add("get", "/admin/register-models", function ($router) {
    $errors = [];

    foreach ($GLOBALS["config_models"] as $m) {
        try {
            $m::register();
        } catch (Exception|Error $e) {
            array_push($errors, strval($e));
            break;
        }
    }

    $GLOBALS["CODE"] = 500;
    $GLOBALS["MESSAGE"] = (sizeof($errors) == 0)
        ? "All models have been successfully registered"
        : "We've encountered some errors. They are be provided in the data field below.";
    $GLOBALS["DATA"] = $errors;
});

$router->listen();