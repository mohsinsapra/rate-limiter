<?php



$payload = [];

try {

    $conn = new mysqli("localhost", "root", "", "wildme");
    // Check connection
    if ($conn->connect_errno) {
        echo "Failed to connect to MySQL: " . $conn->connect_error;
        exit();
    }
    if (
        isset($_POST['loginRateLimiter']) &&
        isset($_POST['username']) &&
        isset($_POST['password']) &&
        isset($_POST['ai_user']) &&
        $_POST['username'] &&
        $_POST['password'] &&
        $_POST['ai_user']
    ) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $cookie = explode("|", $_POST['ai_user'])[0];
        $ip = $_SERVER['REMOTE_ADDR'];



        //Creating table for failed_logins
        $sql = "CREATE TABLE IF NOT EXISTS `failed_logins` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `username` varchar(16) NOT NULL,
            `ip_address` varchar(11) NOT NULL,
            `cookie` text NOT NULL,
            `attempted` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `attempted_idx` (`attempted`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception("Error: Database table not created!!");
        }



        //IP: Logic for 15 times in a hour
        $sql = "SELECT * FROM failed_logins
        WHERE ip_address='{$ip}' AND `attempted` >= addtime(now(), '-01:00:00');";

        $result = $conn->query($sql);

        if ($result->num_rows >= 15) {
            throw new Exception("Error: Sorry you can not attempt to login no more than 15 times in an hour with same IP.");
        }


        //IP: Logic for 5 times in a minute  
        $sql = "SELECT * FROM failed_logins
                WHERE ip_address='{$ip}' AND `attempted` >= addtime(now(), '-00:01:00');";

        $result = $conn->query($sql);

        if ($result->num_rows >= 5) {
            throw new Exception("Error: Sorry you can not attempt to login no more than 5 times in a minute with same IP.");
        }


        //Cookie: Logic for 2 times in 10 seconds
        $sql = "SELECT * FROM failed_logins
                WHERE cookie='{$cookie}' AND `attempted` >= addtime(now(), '-00:00:10');";

        $result = $conn->query($sql);

        if ($result->num_rows >= 2) {
            throw new Exception("Error: Sorry you can not attempt to login no more than 2 times in 10 seconds from same browser.");
        }




        //Username: Logic for 10 times in an hour
        $sql = "SELECT * FROM failed_logins
                WHERE username='{$username}' AND `attempted` >= addtime(now(), '-01:00:00');";

        $result = $conn->query($sql);

        if ($result->num_rows >= 10) {
            throw new Exception("Error: Sorry you can not attempt to login no more than 10 times in an hour using same username.");
        }






        if ($username == "admin" && $password == "123456") {
            $payload['message'] = "You are loggedin";
            die(json_encode($payload));
        } else {

            $sql = "INSERT INTO `failed_logins`( `username`, `ip_address`, `cookie`) 
                VALUES ('{$username}','{$ip}','{$cookie}')";

            if ($conn->query($sql) === TRUE) {
                $payload['message'] = "Username & Password Authentication Failed!";
            } else {
                throw new Exception("Error: " . $sql . "<br>" . $conn->error);
            }
        }


        // print_r($_POST);
    } else {
        throw new Exception("Something went wrong!\n Values are missing!");
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Booboo');
    header('Content-Type: application/json; charset=UTF-8');
    $payload['message'] = 'Message: ' . $e->getMessage();
}

die(json_encode($payload));
