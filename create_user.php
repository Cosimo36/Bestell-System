<?php
require 'db.php'; // deine bestehende Verbindungsdatei

$benutzer = 'marco';
$passwort = password_hash('110469', PASSWORD_DEFAULT); // Passwort sicher hashen

$stmt = $conn->prepare("INSERT INTO benutzer (benutzername, passwort_hash) VALUES (?, ?)");
$stmt->bind_param("ss", $benutzer, $passwort);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Benutzer erfolgreich angelegt.";
} else {
    echo "Fehler beim Einfügen oder Benutzer existiert bereits.";
}

$stmt->close();
$conn->close();
?>
