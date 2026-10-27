
<?php

function splitArtists(string $artistText): array
{
    // Tách tên nghệ sĩ theo các ký hiệu hợp tác
    $parts = preg_split(
        '/\s*(?:,|&|\bft\.?\s+|\bfeat\.?\s+|\bfeaturing\s+|\bx\s+)\s*/iu',
        $artistText
    );

    $result = [];

    foreach ($parts as $part) {
        $name = trim($part);

        if ($name === '') {
            continue;
        }

        $key = mb_strtolower($name, 'UTF-8');
        $result[$key] = $name;
    }

    return array_values($result);
}

function artistMatches(
    string $artistText,
    string $requestedArtist
): bool {
    foreach (splitArtists($artistText) as $name) {
        if (
            mb_strtolower($name, 'UTF-8') ===
            mb_strtolower(trim($requestedArtist), 'UTF-8')
        ) {
            return true;
        }
    }

    return false;
}
