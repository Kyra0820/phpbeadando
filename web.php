<?php
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"]; // Akció: regisztráció vagy bejelentkezés
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($action == "register") {
        // Regisztráció logika
        if (!empty($username) && !empty($password)) {
            // Ellenőrizzük, hogy a felhasználónév már létezik-e
            $userExists = false;
            if (file_exists("users.txt")) {
                $lines = file("users.txt", FILE_IGNORE_NEW_LINES);
                foreach ($lines as $line) {
                    list($existingUsername) = explode(" | ", $line);
                    if (trim($existingUsername) === $username) {
                        $userExists = true;
                        break;
                    }
                }
            }

            if ($userExists) {
                $message = "A felhasználónév már létezik! Válassz másikat.";
            } else {
                // Új felhasználó hozzáadása
                $data = $username . " | " . password_hash($password, PASSWORD_DEFAULT) . "\n";
                file_put_contents("users.txt", $data, FILE_APPEND);
                $message = "Sikeres regisztráció!";
            }
        } else {
            $message = "Minden mező kitöltése kötelező!";
        }
    } elseif ($action == "login") {
        // Bejelentkezés logika
        if (!empty($username) && !empty($password)) {
            $userExists = false;
            if (file_exists("users.txt")) {
                $lines = file("users.txt", FILE_IGNORE_NEW_LINES);
                foreach ($lines as $line) {
                    list($existingUsername, $hashedPassword) = explode(" | ", $line);
                    if (trim($existingUsername) === $username && password_verify($password, trim($hashedPassword))) {
                        $userExists = true;
                        session_start(); // Munkamenet indítása
                        $_SESSION["loggedin"] = true;
                        $_SESSION["username"] = $username;
                        header("Location: application.php"); // Átirányítás az application oldalra
                        exit;
                    }
                }
            }

            if (!$userExists) {
                $message = "Hibás felhasználónév vagy jelszó!";
            }
        } else {
            $message = "Minden mező kitöltése kötelező!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció és Bejelentkezés</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #006400;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        #container {
            display: flex;
            align-items: center;
            background-color: #4CAF50;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        #image {
            margin-right: 20px;
        }
        #image img {
            max-width: 300px;
            border-radius: 10px;
        }
        #form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        input, button {
            margin-bottom: 10px;
            padding: 10px;
            width: 200px;
        }
        button {
            background-color: red;
            cursor: pointer;
            color: white;
            border: none;
            font-weight: bold;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div id="container">
        <div id="image">
            <img src="DALL·E 2025-01-02 15.52.19 - A web page registration form interface with a green background similar to a casino theme. The form includes fields for name and password. In the corne.webp" alt="Registration Form Image">
        </div>
        <div id="form">
            <h2>Regisztráció és Bejelentkezés</h2>
            <form action="" method="post" autocomplete="off">
                <input type="text" name="username" placeholder="Felhasználónév" required autocomplete="off">
                <input type="password" name="password" placeholder="Jelszó" required autocomplete="off">
                <button type="submit" name="action" value="register">Regisztráció</button>
                <button type="submit" name="action" value="login">Bejelentkezés</button>
            </form>
            <p><?php echo $message; ?></p>
        </div>
    </div>
</body>
</html>
