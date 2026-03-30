<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bestell-App</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 2rem;
    }

    h1 {
      font-size: 2rem;
      color: #333;
      margin-bottom: 2rem;
    }

    .menu {
      display: grid;
      gap: 1.5rem;
      width: 100%;
      max-width: 400px;
    }

    .menu a {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      padding: 1.5rem;
      background-color: white;
      color: #333;
      text-decoration: none;
      border-radius: 1rem;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      font-size: 1.2rem;
      font-weight: 600;
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .menu a:hover {
      transform: scale(1.03);
      box-shadow: 0 6px 18px rgba(0,0,0,0.15);
    }

    .icon {
      font-size: 1.5rem;
    }

    @media (max-width: 480px) {
      h1 {
        font-size: 1.5rem;
      }
      .menu a {
        font-size: 1rem;
        padding: 1.2rem;
      }
    }
  </style>
</head>
<body>
  <h1>Was möchtest du tun?</h1>
  <div class="menu">
    <a href="index.php">
      <span class="icon">🏠</span>
      Hauptmenü
    </a>    
    <a href="bestellen_lieferung.php">
      <span class="icon">📝</span>
      Bestellung aufgeben
    </a>
    <a href="ansicht.php">
      <span class="icon">📋</span>
      Bestellungen ansehen
    </a>
  </div>
</body>
</html>