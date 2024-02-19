<?php
session_start();

$server = $_POST['server'] ?? $_SESSION['server'];
$email = $_POST['email'] ?? $_SESSION['email'];
$password = $_POST['password'] ?? $_SESSION['password'];

$_SESSION['server'] = $server;
$_SESSION['email'] = $email;
$_SESSION['password'] = $password;

$mailbox = imap_open("{" . $server . "/imap/ssl}INBOX", $email, $password);

if (!$mailbox) {
    echo "Connection failed: " . imap_last_error();
    exit();
}

$emailsPerPage = 10; // Adjust this number as needed
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $emailsPerPage;
$emails = imap_search($mailbox, 'ALL');
$totalEmails = count($emails);

if ($emails) {
    rsort($emails);
    $emails = array_slice($emails, $offset, $emailsPerPage);

    foreach ($emails as $email_number) {
        $overview = imap_fetch_overview($mailbox, $email_number, 0);
        echo "<a href='view_email.php?email_number=$email_number'>";
        echo "Subject: " . htmlspecialchars($overview[0]->subject) . "<br>";
        echo "From: " . htmlspecialchars($overview[0]->from) . "<br>";
        echo "Date: " . htmlspecialchars($overview[0]->date) . "<br>";
        echo "</a><hr>";
    }

    // Pagination Logic
    $totalPages = ceil($totalEmails / $emailsPerPage);
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            echo "$i ";
        } else {
            echo "<a href='?page=$i'>$i</a> ";
        }
    }
} else {
    echo "No emails found.";
}

imap_close($mailbox);
?>

