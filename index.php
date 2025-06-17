<?php

function getCleanLyrics($url) {
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);
    if (!$html) {
        return "Şarkı sözü bulunamadı.";
    }

    if (preg_match_all('/<div[^>]*data-lyrics-container="true"[^>]*>(.*?)<\/div>/si', $html, $matches)) {
        $lyrics = '';
        foreach ($matches[1] as $block) {
            $block = strip_tags($block, "<br>");
            $block = preg_replace('/<br\s*\/?>/i', "\n", $block);
            $block = html_entity_decode($block, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $block = trim($block);
            if ($block !== '') {
                $lyrics .= $block . "\n\n";
            }
        }
        $lyrics = trim(preg_replace('/\n{3,}/', "\n\n", $lyrics));

        // 1. ContributorsTranslations ve benzeri satırları kaldır
        $lyrics = preg_replace('/^\d+\s*ContributorsTranslations\s*/m', '', $lyrics);

        // 2. Parantez içindeki açıklamaları kaldır (tamamen)
        $lyrics = preg_replace('/\s*\([^)]*\)/', '', $lyrics);

        // 3. Fazla boşlukları temizle
        $lyrics = preg_replace('/[ \t]+/', ' ', $lyrics);
        $lyrics = preg_replace('/\n{3,}/', "\n\n", $lyrics);

        $lyrics = trim($lyrics);

        return $lyrics;
    }

    return "Şarkı sözü bulunamadı.";
}

if (isset($_GET['url'])) {
    $url = $_GET['url'];
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode([
        "url" => $url,
        "lyrics" => getCleanLyrics($url)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    echo "Lütfen ?url=https://genius.com/şarkı-lyrics parametresini kullanın.";
}
