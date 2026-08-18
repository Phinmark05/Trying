<?php
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">   
</head>
<body>
    <div class="container">
        <div class="login-box">
            <form action="simple.php" method="post">
                <h1>WELCOME</h1>
                <p>Enter details below</p>
                <h2>Item bought <input type="text" name="item" id="item" placeholder="Please enter item bought" required></h2>
                <h2>Price: <input type="text" name="price" id="price" placeholder="Please enter price" required></h2>
                <h2> Quantity: <input type="text" name="quantity" id="quantity" placeholder="Please enter amount bought" required></h2>
                <h2> Email: <input type="email" name="email" id="email" placeholder="Please enter email" required></h2>
                <h2>Country: <input type="text" name="country" id="country" placeholder="Please enter country" required></h2>
                <button type="submit" value="submit">Submit</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php 
$items = $_POST['item'];
$quantity = $_POST['quantity'];
$price = $_POST['price'];
$email = $_POST['email'];
$total = $quantity * $price;
if ($quantity > 0 && $price > 0 && !empty($items) && !empty($email)){
echo "Item bought is:  $quantity " . $items ."s". "<with the cost for each item being: " . $price . "<br>";
echo "Therefore the total cost is: " . $total . "<br>";
} else {
    echo "Quantity and price must be greater than 0 and item and email must not be empty";
}


$language = array(
    "Chinese" => "JINSOU",
    "French" => "Bonjour",
    "German" => "Guten Tag",
    "Italian" => "Buongiorno",
    "Japanese" => "Konnichiwa"
);

$country = $language[$_POST['country']];
echo "The language spoken is ". $country;

$items = filter_input(INPUT_POST, "item", FILTER_SANITIZE_NUMBER_INT);
$quantity = filter_input(INPUT_POST, "quantity", FILTER_SANITIZE_NUMBER_INT);
$price = filter_input(INPUT_POST, "price", FILTER_SANITIZE_NUMBER_INT);
$email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);

if (isset($_POST["submit"])){

if(!empty($items) && !empty($quantity) && !empty($price) && !empty($email)){

   $_SESSION["item"] = $_POST["item"];
   $_SESSION["quantity"] = $_POST["quantity"];
   $_SESSION["price"] = $_POST["price"];
   $_SESSION["email"] = $_POST["email"];

   header("Location: main.php");
} else {
    echo "Please fill in all fields";
}
}
