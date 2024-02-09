<?php

$GLOBALS["config_hash_algorithm"] = PASSWORD_DEFAULT;
$GLOBALS["config_admin_username"] = "admin";
$GLOBALS["config_admin_password"] = password_hash("admin", $GLOBALS["config_hash_algorithm"]);

$GLOBALS["config_database_dsn"] = "mysql:host=127.0.0.1:33060;dbname=db";
$GLOBALS["config_database_username"] = "root";
$GLOBALS["config_database_password"] = "admin";

// Follow the sql.php documentation to declare all your models.
// Then update the models array to include the names of your classes.
// https://github.com/mark-veres/sql.php

class User extends \SQL\Record {
    public ?string $username;
    public ?string $password;
}

$GLOBALS["config_models"] = ["User"];