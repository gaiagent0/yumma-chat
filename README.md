# YUMMA AI Chat — WordPress Plugin

AI chat asszisztens a [yummatea.hu](https://yummatea.hu) WooCommerce webshophoz.

> Ez a repo **önálló** — nem függ OCI VM-től vagy más repóktól. A plugin közvetlenül a WordPress szerverről hívja a Qwen API-t.

---

## Stack

- WordPress Plugin API
- WP REST API (`/wp-json/yumma-chat/v1/message`)
- Alibaba DashScope (OpenAI-compatible) — Qwen LLM
- Vanilla JS + CSS (zero dependency)

---

## Funkciók

- 🍵 Lebegő chat widget minden oldalon
- Magyar nyelvű AI asszisztens (Alibaba Qwen API)
- YUMMA termékismeret: fekete, zöld, gyümölcs, gyógynövényes teák, kiegészítők
- WooCommerce REST API-n keresztül kommunikál
- Admin felület: API key, model, pozíció beállítás
- Nonce-alapú CSRF védelem
- Zero külső JS dependency

---

## Architektúra

```
WordPress Frontend (yummatea.hu)
  ↓ lebegő chat widget (Vanilla JS)
  ↓ AJAX → /wp-json/yumma-chat/v1/message  (nonce CSRF védelem)
PHP backend (yumma-chat.php)
  ↓ wp_remote_post
DashScope API (Qwen LLM)
  ↓ Magyar válasz → vissza a widgetbe
```

**Nincs köztes szerver** — a WordPress PHP közvetlenül hívja a Qwen API-t. Nem kell OCI VM, nem kell FastAPI.

---

## Telepítés (éles WordPress)

```bash
# 1. Repo klónozás vagy zip letöltés
git clone https://github.com/gaiagent0/yumma-chat.git

# 2. Plugin mappa másolása
cp -r yumma-chat/yumma-chat /var/www/html/wp-content/plugins/
# vagy Windows/cPanel: FTP-vel töltsd fel a yumma-chat mappát a plugins/ alá
```

Majd WordPress Admin-ban:

1. **Bővítmények → Telepített bővítmények → YUMMA AI Chat → Aktiválás**
2. **Beállítások → YUMMA AI Chat**
3. DashScope API key megadása (lásd lent)
4. Model és pozíció kiválasztása
5. Mentés

---

## API key beszerzése

1. Regisztrálj: [Alibaba Cloud Console](https://dashscope.console.aliyun.com/apiKey)
2. API Keys → Create API Key
3. Másold be a WordPress admin felületen a beállításokba

**Ingyenes kvóta:** DashScope ad ingyenes kredit új regisztrációkhoz.

---

## Beállítások

| Beállítás | Leírás |
|---|---|
| DashScope API Key | Alibaba Cloud API kulcs (`sk-...`) |
| Model | `qwen-turbo` (gyors, olcsó) / `qwen-plus` (ajánlott) / `qwen-max` (legerősebb) |
| Pozíció | Jobb alsó / Bal alsó sarok |

---

## Lokális fejlesztés / tesztelés WordPress nélkül

A plugin PHP-t igényel, de az API hívás önállóan tesztelhető:

```bash
# Qwen API közvetlen teszt (curl)
curl -X POST https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions \
  -H "Authorization: Bearer sk-..." \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen-plus",
    "messages": [
      {"role": "system", "content": "Te a YUMMA tea bolt AI asszisztense vagy. Segíts a vásárlóknak."},
      {"role": "user", "content": "Milyen zöld teákat árultok?"}
    ]
  }'
```

### Lokális WordPress fejlesztői környezet

Ha teljes plugin tesztelést szeretnél:

```bash
# Lehetőség 1: LocalWP (ajánlott Windows-on)
# https://localwp.com — GUI-alapú, egy kattintás

# Lehetőség 2: Docker
docker run -d -p 8080:80 \
  -e WORDPRESS_DB_HOST=db \
  -e WORDPRESS_DB_USER=wp \
  -e WORDPRESS_DB_PASSWORD=wp \
  -e WORDPRESS_DB_NAME=wp \
  --name wordpress wordpress:latest
# majd másold be a plugin mappát a container-be:
docker cp yumma-chat/yumma-chat wordpress:/var/www/html/wp-content/plugins/
```

---

## Hibakeresés

**Chat widget nem jelenik meg:**
- Ellenőrizd: Bővítmények → YUMMA AI Chat → Aktív?
- Browser console: van JS hiba?

**"API hiba" üzenet a chatben:**
- WordPress Admin → Beállítások → YUMMA AI Chat → API key helyes?
- Teszteld a curl paranccsal (lásd fent)
- Alibaba Cloud Console: van egyenleg / ingyenes kvóta?

**CORS hiba (csak lokális fejlesztésnél):**
- Ez nem releváns éles WordPress-nél — a PHP backend-ről megy a hívás, nem böngészőből

---

## Kapcsolódó repók

| Repo | Kapcsolat |
|---|---|
| [hu-ai-chat](https://github.com/gaiagent0/hu-ai-chat) | Hasonló Qwen chat, de önálló FastAPI backend + OCI hosted |
| [portfolio-infra](https://github.com/gaiagent0/portfolio-infra) | OCI infrastruktúra — **nem szükséges** ehhez a pluginhoz |

---

## Licensz

MIT
