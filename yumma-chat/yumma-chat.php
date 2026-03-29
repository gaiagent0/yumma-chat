<?php
/**
 * Plugin Name: YUMMA AI Chat
 * Plugin URI:  https://github.com/gaiagent0/yumma-chat
 * Description: AI chat asszisztens WooCommerce webshophoz — OpenAI-compatible API (Qwen, OpenAI, Groq, Ollama...)
 * Version:     1.1.0
 * Author:      gaiagent0
 * Text Domain: yumma-chat
 */

if (!defined('ABSPATH')) exit;

define('YUMMA_CHAT_VERSION', '1.1.0');

// Default endpoints for quick selection
const YUMMA_CHAT_ENDPOINTS = [
    'https://dashscope-intl.aliyuncs.com/compatible-mode/v1' => 'Alibaba DashScope (Qwen)',
    'https://api.openai.com/v1'                               => 'OpenAI',
    'https://api.groq.com/openai/v1'                          => 'Groq (ingyenes)',
    'https://openrouter.ai/api/v1'                            => 'OpenRouter',
    'http://localhost:11434/v1'                               => 'Ollama (lokalis)',
    'custom'                                                  => 'Egyeni endpoint...',
];

// --- Admin settings ---
add_action('admin_menu', function () {
    add_options_page('YUMMA AI Chat', 'YUMMA AI Chat', 'manage_options', 'yumma-chat', 'yumma_chat_settings_page');
});

add_action('admin_init', function () {
    register_setting('yumma_chat', 'yumma_chat_api_key');
    register_setting('yumma_chat', 'yumma_chat_endpoint', [
        'default' => 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1'
    ]);
    register_setting('yumma_chat', 'yumma_chat_endpoint_custom');
    register_setting('yumma_chat', 'yumma_chat_model', ['default' => 'qwen-plus']);
    register_setting('yumma_chat', 'yumma_chat_position', ['default' => 'bottom-right']);
    register_setting('yumma_chat', 'yumma_chat_system_prompt');
});

function yumma_chat_settings_page() {
    $saved_endpoint = get_option('yumma_chat_endpoint', 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1');
    $is_custom = !array_key_exists($saved_endpoint, YUMMA_CHAT_ENDPOINTS) || $saved_endpoint === 'custom';
    ?>
    <div class="wrap">
        <h1>YUMMA AI Chat <span style="font-size:.7em;color:#888;">v<?php echo YUMMA_CHAT_VERSION; ?></span></h1>
        <form method="post" action="options.php">
            <?php settings_fields('yumma_chat'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">API Endpoint</th>
                    <td>
                        <select name="yumma_chat_endpoint" id="yumma_endpoint_select" onchange="yummaEndpointToggle(this)">
                            <?php foreach (YUMMA_CHAT_ENDPOINTS as $url => $label):
                                $selected = ($url === $saved_endpoint) || ($is_custom && $url === 'custom');
                            ?>
                                <option value="<?php echo esc_attr($url); ?>" <?php selected($selected); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <br><br>
                        <div id="yumma_custom_endpoint_row" style="<?php echo $is_custom ? '' : 'display:none'; ?>">
                            <input type="url" name="yumma_chat_endpoint_custom"
                                value="<?php echo esc_attr(get_option('yumma_chat_endpoint_custom', $is_custom ? $saved_endpoint : '')); ?>"
                                size="60" placeholder="https://myapi.example.com/v1" />
                            <p class="description">OpenAI-compatible /v1 endpoint URL</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" name="yumma_chat_api_key"
                            value="<?php echo esc_attr(get_option('yumma_chat_api_key')); ?>"
                            size="60" placeholder="sk-..." autocomplete="off" />
                        <p class="description">Alibaba: sk-xxx | OpenAI: sk-xxx | Groq: gsk_xxx | Ollama: nincs szukseg</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Model neve</th>
                    <td>
                        <input type="text" name="yumma_chat_model"
                            value="<?php echo esc_attr(get_option('yumma_chat_model', 'qwen-plus')); ?>"
                            size="40" placeholder="qwen-plus" />
                        <p class="description">Pl: qwen-plus, qwen-turbo, gpt-4o-mini, llama-3.1-8b-instant, llama3.2</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Chat widget pozicio</th>
                    <td>
                        <select name="yumma_chat_position">
                            <option value="bottom-right" <?php selected(get_option('yumma_chat_position'), 'bottom-right'); ?>>Jobb also sarok</option>
                            <option value="bottom-left"  <?php selected(get_option('yumma_chat_position'), 'bottom-left'); ?>>Bal also sarok</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Rendszer prompt (system prompt)</th>
                    <td>
                        <textarea name="yumma_chat_system_prompt" rows="8" cols="60"
                            placeholder="Hagyd uresen az alapertelmezett YUMMA prompthoz..."
                        ><?php echo esc_textarea(get_option('yumma_chat_system_prompt')); ?></textarea>
                        <p class="description">Ha ures, az alapertelmezett YUMMA tea webshop prompt lesz hasznalva. Sajat bolthoz ird felul.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Mentés'); ?>
        </form>
    </div>
    <script>
    function yummaEndpointToggle(sel) {
        document.getElementById('yumma_custom_endpoint_row').style.display =
            sel.value === 'custom' ? '' : 'none';
    }
    </script>
    <?php
}

// --- Effective endpoint helper ---
function yumma_chat_get_endpoint() {
    $endpoint = get_option('yumma_chat_endpoint', 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1');
    if ($endpoint === 'custom' || !array_key_exists($endpoint, YUMMA_CHAT_ENDPOINTS)) {
        $endpoint = get_option('yumma_chat_endpoint_custom', $endpoint);
    }
    return rtrim($endpoint, '/');
}

// --- REST API ---
add_action('rest_api_init', function () {
    register_rest_route('yumma-chat/v1', '/message', [
        'methods'             => 'POST',
        'callback'            => 'yumma_chat_handle_message',
        'permission_callback' => '__return_true',
    ]);
});

function yumma_chat_handle_message(WP_REST_Request $request) {
    $api_key  = get_option('yumma_chat_api_key', '');
    $model    = get_option('yumma_chat_model', 'qwen-plus');
    $endpoint = yumma_chat_get_endpoint();
    $messages = $request->get_param('messages');

    if (empty($messages) || !is_array($messages)) {
        return new WP_Error('bad_request', 'Hianyzo uzenet', ['status' => 400]);
    }

    $custom_prompt = get_option('yumma_chat_system_prompt', '');
    $system_prompt = !empty($custom_prompt) ? $custom_prompt : yumma_chat_default_prompt();

    $headers = ['Content-Type' => 'application/json'];
    if (!empty($api_key)) {
        $headers['Authorization'] = 'Bearer ' . $api_key;
    }

    $response = wp_remote_post($endpoint . '/chat/completions', [
        'timeout' => 30,
        'headers' => $headers,
        'body'    => json_encode([
            'model'      => $model,
            'max_tokens' => 600,
            'messages'   => array_merge(
                [['role' => 'system', 'content' => $system_prompt]],
                $messages
            ),
        ]),
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', $response->get_error_message(), ['status' => 502]);
    }

    $data  = json_decode(wp_remote_retrieve_body($response), true);
    $reply = $data['choices'][0]['message']['content'] ?? 'Sajnalom, nem tudtam valaszolni.';

    return rest_ensure_response(['reply' => $reply]);
}

function yumma_chat_default_prompt() {
    return "Te a YUMMA tea webshop (yummatea.hu) baratsagos AI asszisztense vagy.
A YUMMA-rol: magyar szalas tea webshop, minosegi szalas teak es kiegeszitok, ingyenes szallitas 48.990 Ft felett.
Termekek: fekete teak, zold teak, gyumolcs teak, gyogynovenyes teak, ulongok, puerhok, matcha, tea kiegeszitok (bogrek, szurok, kannicskas).
Mindig magyarul valaszolj. Legy udvarias, baratsagos, tomor.
Ha termeket ajanlasz, iranitsd a felhasznalot a kategoriak fele.
Amit nem tudsz biztosan: iranyitsd az ugyfelet az info@yummatea.hu emailre.
NE talalj ki termekeket, arakat, keszletinformaciot.";
}

// --- Frontend widget ---
add_action('wp_footer', 'yumma_chat_render_widget');

function yumma_chat_render_widget() {
    $pos     = get_option('yumma_chat_position', 'bottom-right');
    $pos_css = ($pos === 'bottom-left') ? 'left:24px;' : 'right:24px;';
    ?>
    <style>
    #yumma-chat-btn{position:fixed;bottom:24px;<?php echo $pos_css ?>z-index:9999;background:#5c8a3c;color:#fff;border:none;border-radius:50%;width:56px;height:56px;font-size:26px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:background .2s;}
    #yumma-chat-btn:hover{background:#4a7030;}
    #yumma-chat-box{display:none;position:fixed;bottom:92px;<?php echo $pos_css ?>z-index:9999;width:340px;max-height:520px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.18);flex-direction:column;font-family:'Segoe UI',sans-serif;overflow:hidden;}
    #yumma-chat-box.open{display:flex;}
    #yumma-chat-head{background:#5c8a3c;color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;}
    #yumma-chat-head .yc-title{font-weight:600;font-size:.97rem;}
    #yumma-chat-head .yc-sub{font-size:.75rem;opacity:.85;display:block;}
    #yumma-chat-close{margin-left:auto;background:none;border:none;color:#fff;font-size:1.3rem;cursor:pointer;line-height:1;}
    #yumma-chat-msgs{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;min-height:200px;}
    .yc-msg{max-width:86%;padding:9px 13px;border-radius:12px;font-size:.88rem;line-height:1.5;white-space:pre-wrap;}
    .yc-msg.bot{align-self:flex-start;background:#f0f4ec;color:#222;border-bottom-left-radius:3px;}
    .yc-msg.user{align-self:flex-end;background:#5c8a3c;color:#fff;border-bottom-right-radius:3px;}
    .yc-msg.thinking{opacity:.55;font-style:italic;}
    #yumma-chat-foot{padding:10px;border-top:1px solid #eee;display:flex;gap:8px;}
    #yumma-chat-input{flex:1;border:1px solid #ddd;border-radius:8px;padding:8px 10px;font-size:.88rem;outline:none;resize:none;font-family:inherit;}
    #yumma-chat-input:focus{border-color:#5c8a3c;}
    #yumma-chat-send{background:#5c8a3c;color:#fff;border:none;border-radius:8px;padding:0 14px;cursor:pointer;font-size:1.1rem;}
    #yumma-chat-send:disabled{background:#ccc;cursor:not-allowed;}
    </style>

    <button id="yumma-chat-btn" title="AI Asszisztens">🍵</button>
    <div id="yumma-chat-box">
        <div id="yumma-chat-head">
            <span>🍵</span>
            <div><span class="yc-title">YUMMA Asszisztens</span><span class="yc-sub">Segitunk a teavalasztasban!</span></div>
            <button id="yumma-chat-close" aria-label="Bezaras">&#10005;</button>
        </div>
        <div id="yumma-chat-msgs">
            <div class="yc-msg bot">Szia! A YUMMA tea asszisztense vagyok. 🍵 Miben segithetek?</div>
        </div>
        <div id="yumma-chat-foot">
            <textarea id="yumma-chat-input" rows="2" placeholder="Irj uzzenetet..."></textarea>
            <button id="yumma-chat-send" aria-label="Kuldés">&#10148;</button>
        </div>
    </div>
    <script>
    (function(){
        var btn=document.getElementById('yumma-chat-btn'),
            box=document.getElementById('yumma-chat-box'),
            cls=document.getElementById('yumma-chat-close'),
            msgs=document.getElementById('yumma-chat-msgs'),
            inp=document.getElementById('yumma-chat-input'),
            snd=document.getElementById('yumma-chat-send'),
            API='<?php echo esc_js(rest_url('yumma-chat/v1/message')); ?>',
            NONCE='<?php echo wp_create_nonce('wp_rest'); ?>',
            history=[];

        btn.onclick=function(){box.classList.toggle('open');};
        cls.onclick=function(){box.classList.remove('open');};

        function addMsg(role,text,extra){
            var d=document.createElement('div');
            d.className='yc-msg '+(extra||role);
            d.textContent=text;
            msgs.appendChild(d);
            msgs.scrollTop=msgs.scrollHeight;
            return d;
        }

        function send(){
            var text=inp.value.trim();
            if(!text)return;
            inp.value='';snd.disabled=true;
            history.push({role:'user',content:text});
            addMsg('user',text);
            var t=addMsg('bot','Gondolkodom...','thinking');
            fetch(API,{
                method:'POST',
                headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
                body:JSON.stringify({messages:history})
            }).then(function(r){return r.json();}).then(function(d){
                t.remove();
                var reply=d.reply||'Sajnalom, hiba tortent.';
                history.push({role:'assistant',content:reply});
                addMsg('bot',reply);
            }).catch(function(){
                t.remove();
                addMsg('bot','Kapcsolodasi hiba. Kerlek probald ujra.');
            }).finally(function(){snd.disabled=false;inp.focus();});
        }

        snd.onclick=send;
        inp.addEventListener('keydown',function(e){
            if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}
        });
    })();
    </script>
    <?php
}
