<?php
include("../config.php");

if(isset($_POST['submit'])){
    $name = trim($_POST['name']);
    $msg = trim($_POST['msg']);

    if($name != "" && $msg != ""){
        mysqli_query($conn,"INSERT INTO feedback (user_name,message) VALUES ('$name','$msg')");
        echo "<p style='color:green; text-align:center;'>Thank you for your feedback!</p>";
    }
}

$result = mysqli_query($conn,"SELECT * FROM feedback ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Feedback - E-PRO</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            /* Animated gradient background */
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            background-image: url('../assets/images/graphics/bg-pattern.png'); /* add your graphic image */
            background-repeat: no-repeat;
            background-size: cover;
            background-attachment: fixed;
        }

        h2 {
            text-align: center;
            color: #007bff;
            margin-top: 20px;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }

        .feedback-form {
            width: 90%;
            max-width: 500px;
            margin: 20px auto;
            background: rgba(255,255,255,0.9); /* transparent white card */
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
        }

        .feedback-form input, .feedback-form textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        .feedback-form button {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .feedback-form button:hover {
            background: #218838;
        }

        .reviews {
            width: 90%;
            max-width: 800px;
            margin: 30px auto;
        }

        .review-card {
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .review-card b {
            color: #007bff;
        }

        .review-card p {
            margin: 5px 0 0 0;
            color: #333;
        }
    </style>
</head>
<body>

<h2>Share Your Feedback</h2>

<div class="feedback-form">
    <form method="post">
        <input type="text" name="name" placeholder="Your Name" required>
        <textarea name="msg" placeholder="Write your feedback..." rows="5" required></textarea>
        <button name="submit">Submit Feedback</button>
    </form>
</div>

<div class="reviews">
    <h3 style="text-align:center; color:#007bff;">User Reviews</h3>
    <?php while($row = mysqli_fetch_assoc($result)){ ?>
        <div class="review-card">
            <b><?php echo htmlspecialchars($row['user_name']); ?>:</b>
            <p><?php echo htmlspecialchars($row['message']); ?></p>
        </div>
    <?php } ?>
</div>

</body>
</html>
