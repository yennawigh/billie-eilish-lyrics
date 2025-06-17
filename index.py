from flask import Flask, request, jsonify
import requests
import re
from html import unescape

app = Flask(__name__)

def get_clean_lyrics(url):
    headers = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36"
    }
    try:
        response = requests.get(url, headers=headers, timeout=10)
        html = response.text
    except Exception:
        return "Şarkı sözü bulunamadı."

    matches = re.findall(r'<div[^>]*data-lyrics-container="true"[^>]*>(.*?)</div>', html, re.S)
    if matches:
        lyrics = ''
        for block in matches:
            block = re.sub(r'<br\s*/?>', '\n', block)
            block = re.sub(r'<.*?>', '', block)
            block = unescape(block)
            block = block.strip()
            if block:
                lyrics += block + "\n\n"
        lyrics = re.sub(r'\n{3,}', '\n\n', lyrics)

        # 1. ContributorsTranslations ve benzeri satırları kaldır
        lyrics = re.sub(r'^\d+\s*ContributorsTranslations\s*', '', lyrics, flags=re.M)

        # 2. Parantez içindeki açıklamaları kaldır (tamamen)
        lyrics = re.sub(r'\s*\([^)]*\)', '', lyrics)

        # 3. Fazla boşlukları temizle
        lyrics = re.sub(r'[ \t]+', ' ', lyrics)
        lyrics = re.sub(r'\n{3,}', '\n\n', lyrics)

        lyrics = lyrics.strip()
        return lyrics

    return "Şarkı sözü bulunamadı."

@app.route('/')
def index():
    url = request.args.get('url')
    if url:
        return jsonify({
            "url": url,
            "lyrics": get_clean_lyrics(url)
        })
    else:
        return "Lütfen ?url=https://genius.com/şarkı-lyrics parametresini kullanın."

if __name__ == '__main__':
    app.run()
