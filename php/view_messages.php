<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coming Soon | DataSaver.online</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(to right, #141e30, #243b55);
            font-family: 'Poppins', sans-serif;
            color: #fff;
            text-align: center;
        }
        .container {
            max-width: 600px;
            padding: 20px;
        }
        h1 {
            font-size: 48px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        p {
            font-size: 18px;
            font-weight: 300;
            margin-bottom: 20px;
        }
        .countdown {
            font-size: 24px;
            font-weight: 400;
            margin-top: 20px;
        }
        .social-icons a {
            color: #fff;
            margin: 0 10px;
            text-decoration: none;
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Coming Soon</h1>
        <p>We're working hard to bring you something amazing. Stay tuned!</p>
        <div class="countdown" id="countdown"></div>

    </div>
    <script>
        const launchDate = new Date("March 12, 2025 00:00:00").getTime();
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const timeLeft = launchDate - now;
            
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            document.getElementById("countdown").innerHTML = `Launching in ${days} days - ${hours}h ${minutes}m ${seconds}s`;
            
            if (timeLeft < 0) {
                clearInterval(timer);
                document.getElementById("countdown").innerHTML = "We're Live!";
            }
        }, 1000);
    </script>
</body>
</html>
