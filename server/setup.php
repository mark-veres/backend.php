<?php

require "./config.php";

$method = $_SERVER["REQUEST_METHOD"];
$success = false;

if ($method == "POST") {
    $password_valid = password_verify($_POST["password"], $_CONFIG["admin_password"]);
    if ($_CONFIG["admin_username"] == $_POST["username"] && $password_valid) {
        $conn = new PDO(
            $_CONFIG["database_dsn"],
            $_CONFIG["database_username"],
            $_CONFIG["database_password"]
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $success = $conn->exec($_CONFIG["init_sql"]);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>BaaS Setup</title>
    </head>
    <body>
        <h1>Setup</h1>
        <p>This page can be deleted once the setup has finished.</p>

        <form action="<?php echo $_SERVER["REQUEST_URI"] ?>" method="post">
            <input type="text" name="username" placeholder="admin username"> <br>
            <input type="password" name="password" placeholder="admin password"> <br>
            <input type="submit" value="finish setup">
        </form>

        <?php if ($success) { ?>
            <p>Setup has been finished.</p>
        <?php } else { ?>
            <p>Error was encountered.</p>
        <?php } ?>
    </body>
</html>