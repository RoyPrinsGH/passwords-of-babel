<?php

class BabelLocation
{
    public function __construct(
        public string $room,
        public int $wall,
        public int $shelf,
        public int $book,
        public int $page,
        public int $fromLine,
        public int $fromLineIndex,
        public int $untilLine,
        public int $untilLineIndex,
    ) {}

    public function toString(): string
    {
        return sprintf(
            '@%s.%d.%d.%d.%d:%d:%d:%d:%d',
            $this->room,
            $this->wall,
            $this->shelf,
            $this->book,
            $this->page,
            $this->fromLine,
            $this->fromLineIndex,
            $this->untilLine,
            $this->untilLineIndex,
        );
    }

    public static function fromString(string $input): self
    {
        // libraryofbabel.app rooms are base-32: [0-9a-v], not hex.
        $pattern = '/^@?([0-9a-v]+)\.(\d+)\.(\d+)\.(\d+)\.(\d+):(\d+):(\d+):(\d+):(\d+)$/i';

        if (!preg_match($pattern, $input, $matches)) {
            throw new InvalidArgumentException("Invalid BabelLocation string: {$input}");
        }

        return new self(
            strtolower($matches[1]),
            (int) $matches[2],
            (int) $matches[3],
            (int) $matches[4],
            (int) $matches[5],
            (int) $matches[6],
            (int) $matches[7],
            (int) $matches[8],
            (int) $matches[9],
        );
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}

function babelHttpRequest(
    string $url,
    ?string $method = null,
    ?string $body = null,
    array $headers = [],
): string {
    $ch = curl_init($url);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($method !== null) {
        $options[CURLOPT_CUSTOMREQUEST] = $method;
    }

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        throw new RuntimeException("cURL error: {$error}");
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException("HTTP error {$statusCode}: {$response}");
    }

    return $response;
}

function searchLibraryOfBabel(string $input, string $mode = 'chars'): BabelLocation
{
    $payload = json_encode([
        'content' => $input,
        'mode' => $mode,
    ], JSON_THROW_ON_ERROR);

    $response = babelHttpRequest(
        'https://libraryofbabel.app/do-search',
        'POST',
        $payload,
        [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    );

    $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    if (!isset($data['ref']) || !is_string($data['ref'])) {
        throw new RuntimeException('Search response did not contain a valid ref.');
    }

    $ref = $data['ref'];
    $highlight = $data['highlight'] ?? null;

    if ($highlight === null || !is_string($highlight)) {
        throw new RuntimeException(
            "Search mode '{$mode}' did not return a highlight. Use mode 'chars' or 'words'."
        );
    }

    $fullRef = babelHttpRequest(
        'https://libraryofbabel.app/fullref/' . rawurlencode($ref),
        'GET',
        null,
        [
            'Accept: text/plain',
        ],
    );

    $fullRef = trim($fullRef);

    return BabelLocation::fromString($fullRef . ':' . $highlight);
}

function copyEnormousTextToClipboard(string $text): void
{
    $process = proc_open(
        ['wl-copy', '--type', 'text/plain;charset=utf-8'],
        [
            0 => ['pipe', 'r'],
            1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w'],
        ],
        $pipes
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Failed to start wl-copy.');
    }

    $length = strlen($text);
    $offset = 0;
    $chunkSize = 1024 * 1024;

    while ($offset < $length) {
        $chunk = substr($text, $offset, min($chunkSize, $length - $offset));
        $written = fwrite($pipes[0], $chunk);

        if ($written === false) {
            fclose($pipes[0]);
            proc_close($process);

            throw new RuntimeException('Failed to write to wl-copy.');
        }

        if ($written === 0) {
            usleep(10_000);
            continue;
        }

        $offset += $written;
    }

    fclose($pipes[0]);

    // Should return quickly now, because stdout/stderr are not pipes held open by wl-copy.
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        throw new RuntimeException("wl-copy failed with exit code {$exitCode}.");
    }
}
