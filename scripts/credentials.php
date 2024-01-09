<?php
// ATTENTION: This script is not to be publicly
// accessible on the server, as to not be accessed
// by unauthorized parties.

echo "This script will prompt the user to enter a\n";
echo "password and the hash algorithm, outputing\n";
echo "the hashed version.\n\n";

echo "BE SURE THAT THE CHOSEN ALGORITHM IS THE SAME\n";
echo "AS THE ONE ONE CHOSEN IN THE CONFIG FILE.\n\n";

echo "Enter the hash algorithm:\n";
echo "- PASSWORD_DEFAULT (default one, press enter to skip)\n";
echo "- PASSWORD_BCRYPT\n";
echo "- PASSWORD_ARGON2I\n";
echo "- PASSWORD_ARGON2ID\n";
echo "->";

$handle = fopen("php://stdin", "r");
$algorithm = trim(fgets($handle));
fclose($handle);

if ($algorithm == "") {
    $algorithm = "PASSWORD_DEFAULT";
}

$algorithms = ["PASSWORD_DEFAULT", "PASSWORD_BCRYPT", "PASSWORD_ARGON2I", "PASSWORD_ARGON2ID"];
if (!in_array($algorithm, $algorithms)) {
    echo "\nError: invalid algorithm\n";
    die();
}

echo "\n(the password will be shown on screen)\n";
echo "Enter your admin password:\n";
echo "->";

$handle = fopen("php://stdin", "r");
$password = trim(fgets($handle));
fclose($handle);

if (strlen($password) <= 0) {
    echo "\nError: the password must be longer\n";
    die();
}

$algorithm = match ($algorithm) {
    "PASSWORD_DEFAULT" => PASSWORD_DEFAULT,
    "PASSWORD_BCRYPT" => PASSWORD_BCRYPT,
    "PASSWORD_ARGON2I" => PASSWORD_ARGON2I,
    "PASSWORD_ARGON2ID" => PASSWORD_ARGON2I,
};

echo "\n" . password_hash($password, $algorithm) . "\n";