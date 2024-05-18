<?php
session_start(); // start the session at the beginning of your script

// database connection details
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "canteen";

// create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// array product prices
$prices = array(
    "Fishball" => 30,
    "Kikiam" => 40,
    "Corndog" => 50
);
// declare variables
$error = null;
$output = null;
$quantity = '';
$cash = '';

// user registration
if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // insert user into database
    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $stmt->close();

    // redirect to login page after registration
    header("Location: " . $_SERVER['PHP_SELF'] . "?page=login");
    exit();
}

// user login
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // check if username and password match
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['username'] = $username;
    }

    $stmt->close();
}

// process order (only if the user is logged in)
$formSubmitted = isset($_POST['submit']);
if (isset($_SESSION['username']) && $formSubmitted) {
    $order = isset($_POST['order']) ? $_POST['order'] : "";
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $cash = isset($_POST['cash']) ? (float)$_POST['cash'] : 0.0;

    $totalCost = $prices[$order] * $quantity;
    $change = $cash - $totalCost;

    $output = "<h2>The total cost is $totalCost PHP</h2>";
    $output .= "<h2>Your change is $change PHP</h2>";
    $output .= "<h4>Thanks for the order! <span class='blue-text'>{$_SESSION['username']}</span></h4>";
    $output .= '<a href="?logout=true"><button>Logout</button></a> ';
    $output .= '<a href="'.$_SERVER['PHP_SELF'].'"><button>Order Again</button></a>';
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Canteen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        h3, h2 {
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 5px;
        }
        input[type="text"], input[type="password"], input[type="number"], select {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        input[type="submit"] {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .blue-text {
            color: blue;
        }
        a {
            color: #007bff;
            text-decoration: none;
            margin-top: 10px;
            display: inline-block;
        }
        a:hover {
            text-decoration: underline;
        }
       
        button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // determine which form to show based on the query parameter
        $page = isset($_GET['page']) ? $_GET['page'] : 'login';

        // show the appropriate form
        if (!isset($_SESSION['username'])) {
            if ($page == 'register') {
                // registration form
                ?>
                <h3>Register</h3>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <label for="reg_username">Username:</label>
                    <input type="text" name="username" id="reg_username" required>
                    <label for="reg_password">Password:</label>
                    <input type="password" name="password" id="reg_password" required>
                    <input type="submit" name="register" value="Register">
                </form>
                <a href="?page=login">Login</a>
                <?php
            } else {
                // login form
                ?>
                <h3>Login</h3>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <label for="login_username">Username:</label>
                    <input type="text" name="username" id="login_username" required>
                    <label for="login_password">Password:</label>
                    <input type="password" name="password" id="login_password" required>
                    <input type="submit" name="login" value="Login">
                </form>
                <a href="?page=register">Register</a>
                <?php
            }
        }

        // show order form and logout link if the user is logged in
        if (isset($_SESSION['username'])) {
            if (!$formSubmitted) {
                ?>
                <h2>Welcome to the canteen, <span class="blue-text"><?php echo $_SESSION['username']; ?></span></h2>
                <a href="?logout=true"><button>Logout</button></a>
                <h2>Here are the prices:</h2>
                <ul>
                    <?php foreach ($prices as $item => $price) { ?>
                        <li><?php echo $item; ?> - <?php echo $price; ?> PHP</li>
                    <?php } ?>
                </ul>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <label for="order">Choose your order:</label>
                    <select name="order" id="order">
                        <?php foreach ($prices as $item => $price) { ?>
                            <option value="<?php echo $item; ?>"><?php echo $item; ?></option>
                        <?php } ?>
                    </select>
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" min="1" value="<?php echo $quantity; ?>" required>
                    <label for="cash">Cash:</label>
                    <input type="number" name="cash" id="cash" min="0" value="<?php echo $cash; ?>" required>
                    <input type="submit" name="submit" value="Submit">
                </form>
                <?php
            } else {
                // display output if form is submitted
                if ($output) {
                    echo $output;
                }
            }
        }

        // logout 
        if (isset($_GET['logout']) && isset($_SESSION['username'])) {
            session_unset();
            session_destroy();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        ?>
    </div>
</body>
</html>
