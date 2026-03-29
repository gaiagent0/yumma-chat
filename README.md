# YUMMA AI Chat — WordPress Plugin

AI chat asszisztens a [yummatea.hu](https://yummatea.hu) WooCommerce webshophoz.

## Funkciók

- 🍵 Lebegő chat widget minden oldalon
- Magyar nyelvű AI asszisztens (Alibaba Qwen API)
- YUMMA termékismeret: fekete, zöld, gyümölcs, gyógynövényes teák, kiegészítők
- WooCommerce REST API-n keresztül kommunikál
- Admin felület: API key, model, pozíció beállítás
- Nonce-alapú CSRF védelem
- Zero külső JS dependency

## Telepítés

1. Töltsd le a `yumma-chat` mappát
2. Másold a WordPress `/wp-content/plugins/` könyvtárba
3. WordPress Admin → Bővítmények → YUMMA AI Chat aktiválása
4. Beállítások → YUMMA AI Chat → DashScope API key megadása

## Beállítások

| Beállítás | Leírás |
|---|---|
| DashScope API Key | Alibaba Cloud API kulcs |
| Model | qwen-turbo (gyors) / qwen-plus (ajánlott) / qwen-max (erős) |
| Pozíció | Jobb alsó / Bal alsó sarok |

## Architektúra

```
WordPress Frontend
  → REST API (/wp-json/yumma-chat/v1/message)
    → PHP backend (wp_remote_post)
      → DashScope API (Qwen LLM)
        → Magyar válasz
```

## Stack

- WordPress Plugin API
- WP REST API
- Alibaba DashScope (OpenAI-compatible)
- Vanilla JS + CSS (zero dependency)
