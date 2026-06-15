<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Webklex\PHPIMAP\ClientManager;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env', 'prod');

$host = $_ENV['BOUNCE_IMAP_HOST'] ?? null;
$port = (int) ($_ENV['BOUNCE_IMAP_PORT'] ?? 993);
$encryption = $_ENV['BOUNCE_IMAP_ENCRYPTION'] ?? 'ssl';
$username = $_ENV['BOUNCE_IMAP_USERNAME'] ?? null;
$password = $_ENV['BOUNCE_IMAP_PASSWORD'] ?? null;
$folderName = $_ENV['BOUNCE_IMAP_FOLDER'] ?? 'INBOX';

if (!$host || !$username || !$password) {
    fwrite(STDERR, "Bounce IMAP-configuratie is niet compleet.\n");
    exit(1);
}

$clientManager = new ClientManager();

$client = $clientManager->make([
    'host' => $host,
    'port' => $port,
    'encryption' => $encryption,
    'validate_cert' => true,
    'username' => $username,
    'password' => $password,
    'protocol' => 'imap',
]);

try {
    $client->connect();

    echo "IMAP-verbinding geslaagd.\n";

    $folder = $client->getFolder($folderName);

    if ($folder === null) {
        fwrite(STDERR, sprintf("Map '%s' niet gevonden.\n", $folderName));
        exit(1);
    }

    $messages = $folder->messages()
        ->all()
        ->limit(10)
        ->get();

    echo sprintf(
        "De laatste %d bericht(en) zijn gevonden:\n\n",
        count($messages)
    );

    foreach ($messages as $message) {
    $rawSubject = (string) $message->getSubject();

    $subject = iconv_mime_decode(
        $rawSubject,
        ICONV_MIME_DECODE_CONTINUE_ON_ERROR,
        'UTF-8'
    );

    $textBody = (string) ($message->getTextBody() ?? '');
    $rawBody = (string) $message->getRawBody();
    $content = $textBody . "\n" . $rawBody;

    $recipient = null;
    $status = null;
    $diagnostic = null;

    // Standaard DSN-formaat:
    // Final-Recipient: rfc822; gebruiker@example.nl
    if (preg_match(
        '/Final-Recipient:\s*(?:rfc822;)?\s*<?([^\s<>;]+@[^\s<>;]+)>?/i',
        $content,
        $match
    )) {
        $recipient = strtolower(trim($match[1]));
    }

    // Alternatief DSN-formaat.
    if ($recipient === null && preg_match(
        '/Original-Recipient:\s*(?:rfc822;)?\s*<?([^\s<>;]+@[^\s<>;]+)>?/i',
        $content,
        $match
    )) {
        $recipient = strtolower(trim($match[1]));
    }

    // Exim-tekst zoals:
    // The following address(es) failed:
    // gebruiker@example.nl
    if ($recipient === null && preg_match(
        '/following address\(es\) failed:\s*(?:\r?\n)+\s*<?([^\s<>]+@[^\s<>]+)>?/i',
        $content,
        $match
    )) {
        $recipient = strtolower(trim($match[1]));
    }

    if (preg_match(
        '/^Status:\s*([245]\.\d+\.\d+)/mi',
        $content,
        $match
    )) {
        $status = trim($match[1]);
    }

    if (preg_match(
        '/^Diagnostic-Code:\s*(.+)$/mi',
        $content,
        $match
    )) {
        $diagnostic = trim($match[1]);
    }

    echo sprintf(
        "- %s\n  Onderwerp: %s\n  Adres: %s\n  Status: %s\n  Reden: %s\n\n",
        $message->getDate()?->toDate()?->format('Y-m-d H:i:s') ?? 'geen datum',
        $subject ?: $rawSubject,
        $recipient ?? 'niet gevonden',
        $status ?? 'niet gevonden',
        $diagnostic ?? 'niet gevonden'
    );
}

    $client->disconnect();
} catch (Throwable $exception) {
    fwrite(
        STDERR,
        sprintf(
            "IMAP-test mislukt: %s\n",
            $exception->getMessage()
        )
    );

    exit(1);
}