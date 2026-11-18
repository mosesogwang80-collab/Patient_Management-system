<?php
// Optional: you can include header or navbar later
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Booked</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            text-align: center;
            padding: 50px;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 450px;
            margin: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: green;
        }
        a {
            text-decoration: none;
            background: blue;
            padding: 10px 20px;
            color: white;
            border-radius: 6px;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Appointment Successfully Booked!</h2>
    <p>Your appointment has been submitted and is waiting for doctor approval.</p>

    <a href="booking.php">Book Another Appointment</a>
</div>

</body>
</html>
