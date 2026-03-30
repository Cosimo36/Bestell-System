<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $benutzer = $_POST['benutzer'] ?? '';
    $passwort = $_POST['passwort'] ?? '';

    $stmt = $conn->prepare("SELECT id, passwort_hash FROM benutzer WHERE benutzername = ?");
    $stmt->bind_param("s", $benutzer);
    $stmt->execute();
    $stmt->bind_result($id, $hash);
    if ($stmt->fetch() && password_verify($passwort, $hash)) {
        $_SESSION['user_id'] = $id;
        header("Location: index.php");
        exit;
    } else {
        $fehler = "Ungültige Anmeldedaten!";
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
      display: flex;
      height: 100vh;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 90%;
      max-width: 400px;
    }

    h2 {
      margin-top: 0;
      text-align: center;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      margin-bottom: 1rem;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 1rem;
    }

    button {
      width: 100%;
      padding: 0.75rem;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s;
    }

    button:hover {
      background: #0056b3;
    }

    .fehler {
      color: red;
      text-align: center;
      margin-top: 1rem;
    }
  </style>
</head>
<body>

<div class="login-container">
  <h2>Login</h2>
  <form method="post">
    <label for="benutzer">Benutzername</label>
    <input type="text" id="benutzer" name="benutzer" required>

    <label for="passwort">Passwort</label>
    <input type="password" id="passwort" name="passwort" required>

    <button type="submit">Anmelden</button>
    <?php if (!empty($fehler)) echo "<p class='fehler'>$fehler</p>"; ?>
  </form>
</div>

</body>
</html>
