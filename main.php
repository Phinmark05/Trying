<?php
if ($_SERVER["REQUEST_METHOD"] == "POST"){

$name = $_POST["name"];
$email = $_POST["email"];

} else {
    echo "No details where inserted";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN RESULTS</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h2>LOGIN SUCCESSFUL</h2>
            <p> HELLO: <?php echo "$name; "?></p>
            <p> Your email is: <?php echo "$email; "?></p>
        </div>
    </div>
    
</body>
</html>