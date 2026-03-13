<?php
include 'includes/auth.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - GBI Salemba</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F9FB;
            color: #102C57;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #EADBC8;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #102C57;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .welcome {
            font-size: 16px;
            margin-bottom: 30px;
            color: #146C94;
        }
        .menu-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        .menu-list li {
            margin-bottom: 15px;
        }
        .menu-list a {
            display: inline-block;
            background-color: #146C94;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }
        .menu-list a:hover {
            background-color: #0F4A6B;
        }
        .logout {
            display: inline-block;
            background-color: #d32f2f;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-weight: 500;
            margin-top: 20px;
        }
        .logout:hover {
            background-color: #b71c1c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard Admin</h1>
        <p class="welcome">Selamat datang, <strong><?php echo $_SESSION['username']; ?></strong></p>

        <h3>Menu Kelola</h3>
        <ul class="menu-list">
            <li><a href="slider.php">Kelola Slider</a></li>
            <li><a href="whatsnew.php">Kelola What's New</a></li>
            <li><a href="jadwal.php">Kelola Jadwal</a></li>
            <li><a href="pelayanan.php">Kelola Pelayanan</a></li>
            <li><a href="renungan.php">Kelola Renungan</a></li>
            <li><a href="formulir.php">Kelola Formulir</a></li>
        </ul>

        <a href="logout.php" class="logout">Logout</a>
    </div>
</body>
</html>