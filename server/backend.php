<?php
// This file contains all the backend code.
// DO NOT MODIFY THE CONTENTS OF THIS
// FILE UNLESS YOU KNOW WHAT YOU'RE DOING.

require "./config.php";

// Global variables that can be used by used by
// user-defined functions in the config file.
$GLOBALS["RESULT_CODE"] = 200;
$GLOBALS["RESULT_MESSAGE"] = "";
$GLOBALS["RESULT_DATA"] = NULL;

// The client-server communication will
// only happen through JSON objects.
header('Content-Type: application/json');

if ($_CONFIG["enable_sessions"]) {
    session_start();
}

$method = $_SERVER["REQUEST_METHOD"];
$path = strtolower($_SERVER["PATH_INFO"]);
$is_file_upload = str_starts_with($_SERVER["CONTENT_TYPE"], "multipart/form-data");

function result() {
    die(json_encode([
        "code" => $GLOBALS["RESULT_CODE"],
        "message" => $GLOBALS["RESULT_MESSAGE"],
        "data" => $GLOBALS["RESULT_DATA"]
    ]));
}

try {
    $conn = new PDO(
        $_CONFIG["database_dsn"],
        $_CONFIG["database_username"],
        $_CONFIG["database_password"]
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $GLOBALS["RESULT_CODE"] = 500;
    $GLOBALS["RESULT_MESSAGE"] = $e->getMessage();
    result();
}

// Every request that starts with "/query"
// executes the config-defined queries.
if (str_starts_with($path, "/query")) {
    foreach ($_CONFIG["queries"] as $name => $options) {
        if ($path != "/query/".$name) continue;

        // The user-defined "gate function"
        // allows all requests by default.
        $gate = $options["gate"] ?? function() { return true; };
        $sql = $options["query"] ?? "";
        // "redirect_back" is useful for HTML forms.
        $redirect_back = $options["redirect-back"] ?? false;
        $param_transformers = $options["params"] ?? [];

        // Run the user-defined function that
        // allows the passage of requests.
        $allow_request = call_user_func($gate);
        if (!$allow_request) {
            $GLOBALS["RESULT_CODE"] = 403;
            $GLOBALS["RESULT_MESSAGE"] = "forbidden";
            result();
        }
        
        // If the SQL query is absent, skip this part as
        // there is not purpose in querying the database.
        $result = NULL;
        if ($sql != "") try {
            // It differentiates between two types of queries,
            // ones that have no dynamic parameters and ones
            // that contain ":", therefore have them.
            if (str_contains($sql, ":")) {
                $request_data = ($method == "POST") ? $_POST : $_GET;

                $stmt = $conn->prepare($sql);

                foreach ($request_data as $param => $value) {
                    if (isset($param_transformers[$param]) &&
                        $param_transformers[$param] == "omit")
                        continue;

                    // User defined parameter transformers can modify
                    // the value of a parameter. Useful for hashing
                    // passwords, normalizing data, etc.
                    // Optionally, they can also be used to modify the
                    // behaviour of the program when this parameter is met.
                    if (isset($param_transformers[$param]))
                        $request_data[$param] = $param_transformers[$param]($value);
                    
                    $stmt->bindParam(":".$param, $request_data[$param]);
                }

                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Absence of dynamic parameters allows us to
                // use the simpler query function insetead of
                // using prepared statements.
                $stmt = $conn->query($sql, PDO::FETCH_ASSOC);
                $result = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $GLOBALS["RESULT_CODE"] = 500;
            $GLOBALS["RESULT_MESSAGE"] = $e->getMessage();
            result();
        }

        if (isset($_FILES) && $is_file_upload) {
            foreach ($_FILES as $name => $file) {
                // The file can be manipulated by a config-defined transformer.
                if (isset($param_transformers[$name]))
                    $file = call_user_func($param_transformers[$name], $file);
                
                // Change name according to user-defined naming scheme
                $new_name = basename(call_user_func($_CONFIG["file_upload_naming_scheme"], $file));
                $target_file = $_CONFIG["file_upload_folder"].$new_name;
                $file_name = pathinfo($target_file, PATHINFO_FILENAME);
                $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                if (file_exists($target_file)) {
                    $GLOBALS["RESULT_CODE"] = 500;
                    $GLOBALS["RESULT_MESSAGE"] = "file already exists";
                    result();
                } else if ($file["size"] > $_CONFIG["file_upload_size_limit"]) {
                    $GLOBALS["RESULT_CODE"] = 500;
                    $GLOBALS["RESULT_MESSAGE"] = "file too large";
                    result();
                } else if (
                    // An empty "allowed types" array is interpreted
                    // as accepting all file types.
                    sizeof($_CONFIG["file_upload_allowed_types"]) != 0 &&
                    !in_array($file_type, $_CONFIG["file_upload_allowed_types"])) {
                        $GLOBALS["RESULT_CODE"] = 500;
                        $GLOBALS["RESULT_MESSAGE"] = "file type not allowed";
                        result();
                }
            }
        }

        if ($redirect_back) {
            header('Location: ' . $_SERVER["HTTP_REFERER"]);
            exit();
        }

        $GLOBALS["RESULT_CODE"] = 200;
        $GLOBALS["RESULT_DATA"] = $result;
        result();
    }
}

$GLOBALS["RESULT_CODE"] = 404;
$GLOBALS["RESULT_MESSAGE"] = "wrong path";
result();