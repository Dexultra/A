<?php
session_start();

function decodeMimeStr($string, $charset = 'UTF-8') {
    $newString = '';
    $elements = imap_mime_header_decode($string);
    for ($i = 0; $i < count($elements); $i++) {
        if ($elements[$i]->charset == 'default') {
            $elements[$i]->charset = 'ASCII';
        }
        $newString .= iconv($elements[$i]->charset, $charset, $elements[$i]->text);
    }
    return $newString;
}

function getPart($mailbox, $msgNumber, $mimeType, $structure = false, $partNumber = false) {
    if (!$structure) {
        $structure = imap_fetchstructure($mailbox, $msgNumber);
    }
    if ($structure) {
        if ($mimeType == getMimeType($structure)) {
            if (!$partNumber) {
                $partNumber = 1;
            }
            $text = imap_fetchbody($mailbox, $msgNumber, $partNumber);
            if ($structure->encoding == 3) {
                return imap_base64($text);
            } else if ($structure->encoding == 4) {
                return imap_qprint($text);
            } else {
                return $text;
            }
        }

        // Recursive call for multi-part emails
        if ($structure->type == 1) {
            foreach ($structure->parts as $index => $subStruct) {
                $prefix = "";
                if ($partNumber) {
                    $prefix = $partNumber . '.';
                }
                $data = getPart($mailbox, $msgNumber, $mimeType, $subStruct, $prefix . ($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}

function getMimeType($structure) {
    $primaryMimeType = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype) {
        return $primaryMimeType[(int) $structure->type] . '/' . $structure->subtype;
    }
    return "TEXT/PLAIN";
}

$mailbox = imap_open("{" . $_SESSION['server'] . "/imap/ssl}INBOX", $_SESSION['email'], $_SESSION['password']);

if (!$mailbox) {
    echo "Connection failed: " . imap_last_error();
    exit();
}

$email_number = $_GET['email_number'];

$overview = imap_fetch_overview($mailbox, $email_number, 0);
$structure = imap_fetchstructure($mailbox, $email_number);

$body = '';
if ($structure) {
    if ($structure->type == 1) { // multipart
        $body = getPart($mailbox, $email_number, "TEXT/HTML");
        if (!$body) {
            $body = getPart($mailbox, $email_number, "TEXT/PLAIN");
        }
    } else { // not multipart
        $body = imap_body($mailbox, $email_number);
    }
}

echo "<strong>Subject:</strong> " . decodeMimeStr($overview[0]->subject) . "<br>";
echo "<strong>From:</strong> " . decodeMimeStr($overview[0]->from) . "<br>";
echo "<strong>Date:</strong> " . $overview[0]->date . "<br>";
echo "<hr>";

// Sanitize and display the HTML content
// Note: For better security, consider using a library like HTML Purifier
echo "<iframe srcdoc='" . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . "' width='100%' height='600px'></iframe>";

imap_close($mailbox);
?>
