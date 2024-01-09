<?php

$_CONFIG["hash_algorithm"] = PASSWORD_DEFAULT;
$_CONFIG["admin_username"] = "admin";
$_CONFIG["admin_password"] = password_hash("admin", $_CONFIG["hash_algorithm"]);

$_CONFIG["database_dsn"] = "mysql:host=127.0.0.1:33060;dbname=db";
$_CONFIG["database_username"] = "root";
$_CONFIG["database_password"] = "admin";

$_CONFIG["enable_file_upload"] = true;
$_CONFIG["file_upload_size_limit"] = 1000 * 1000 * 10; // 10MB
// An empty array will mean that all file types are allowed
$_CONFIG["file_upload_allowed_types"] = [];
$_CONFIG["file_upload_folder"] = "uploads/";
$_CONFIG["file_upload_naming_scheme"] = function($value) {
    return pathinfo($value["name"], PATHINFO_FILENAME).time().".".pathinfo($value["name"], PATHINFO_EXTENSION);
};

$_CONFIG["enable_sessions"] = true;

$_CONFIG["queries"] = [
    "users-list" => [
        "query" => "select * from users",
        "gate" => function() {
            return true;
        }
    ],
    "users-add" => [
        "query" => "insert into users (ID, username, password_hash) values (:id, :username, :password)",
        "params" => [
            "password" => function($value) {
                return password_hash($value, $_CONFIG["hash_algorithm"]);
            },
            "test" => "omit"
        ],
        "redirect-back" => true
    ],
    "upload" => [
        "params" => [
            "file" => function($value) {
                return $value;
            }
        ]
    ]
];

$_CONFIG["init_sql"] = <<<SQL
    CREATE TABLE users (
        ID int DEFAULT 0,
        username VARCHAR(255) NOT NULL UNIQUE,
        password_hash TEXT(1000),
        PRIMARY KEY (ID)
    );
SQL;