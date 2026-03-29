<?php
/**
 * Plugin Name: YUMMA AI Chat
 * Plugin URI: https://github.com/gaiagent0/yumma-chat
 * Description: AI chat asszisztens a YUMMA tea webshophoz — Qwen API alapon
 * Version: 1.0.0
 * Author: gaiagent0
 * Text Domain: yumma-chat
 */

if (!defined('ABSPATH')) exit;

define('YUMMA_CHAT_VERSION', '1.0.0');
define('YUMMA_CHAT_DIR', plugin_dir_path(__FILE__));
define('YUMMA_CHAT_URL', plugin_dir_url(__FILE__));

// --- Settings ---
add_action('admin_menu', function () {
    add_options_page('YUMMA AI Chat', 'YUMMA AI Chat', 'manage_options', 'yumma-chat', 'yumma_chat_settings_page');
});

add_action('admin_init', function () {
    register_setting('yumma_chat', 'yumma_chat_api_key');
    register_setting('yumma_chat', 'yumma_chat_model', ['default' => 'qwen-plus']);
    register_setting('yumma_chat', 'yumma_chat_position', ['default' => 'bottom-right']);
});

function yumma_chat_settings_page() { ?>
    <div class="wrap">
        <h1>YUMMA AI Chat Beallitasok</h1>
        <form method="post" action="options.php">
            <?php settings_fields('yumma_chat'); ?>
            <table class="form-table">
                <tr>
                    <th>DashScope API Key</th>
                    <td><input type="password" name="yumma_chat_api_key" value="<?php echo esc_attr(get_option('yumma_chat_api_key')); ?>" size="60" /></td>
                </tr>
                <tr>
                    <th>Model</th>
                    <td>
                        <select name="yumma_chat_model">
                            <?php foreach (['qwen-turbo' => 'qwen-turbo (gyors)', 'qwen-plus' => 'qwen-plus (ajanlott)', 'qwen-max' => 'qwen-max (eros)'] as $v => $l): ?>
                                <option value="<?php echo $v; ?>" <?php selected(get_option('yumma_chat_model'), $v); ?>><?php echo $l; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>Pozicio</th>
                    <td>
                        <select name="yumma_chat_position">
                            <option value="bottom-right" <?php selected(get_option('yumma_chat_position'), 'bottom-right'); ?>>Jobb alsó</option>
                            <option value="bottom-left" <?php selected(get_option('yumma_chat_position'), 'bottom-left'); ?>>Bal alsó</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

// --- REST API endpoint ---
add_action('rest_api_init', function () {
    register_rest_route('yumma-chat/v1', '/message', [
        'methods'  => 'POST',
        'callback' => 'yumma_chat_handle_message',
        'permission_callback' => '__return_true',
    ]);
});

function yumma_chat_handle_message(WP_REST_Request $request) {
    $api_key = get_option('yumma_chat_api_key');
    $model   = get_option('yumma_chat_model', 'qwen-plus');
    $messages = $request->get_param('messages');

    if (!$api_key) {
        return new WP_Error('no_api_key', 'API kulcs nincs beallitva', ['status' => 500]);
    }
    if (empty($messages) || !is_array($messages)) {
        return new WP_Error('bad_request', 'Hianyzo uzenet', ['status' => 400]);
    }

    $system_prompt = yumma_chat_system_prompt();

    $body = json_encode([
        'model'      => $model,
        'max_tokens' => 600,
        'messages'   => array_merge(
            [['role' => 'system', 'content' => $system_prompt]],
            $messages
        ),
    ]);

    $response = wp_remote_post('https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions', [
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return new WP_Error('api_error', $response->get_error_message(), ['status' => 502]);
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $reply = $data['choices'][0]['message']['content'] ?? 'Sajnalom, nem tudtam valaszolni.';

    return rest_ensure_response(['reply' => $reply]);
}

function yumma_chat_system_prompt() {
    return "Te a YUMMA tea webshop (yummatea.hu) baratsagos AI asszisztense vagy.
A YUMMA-rol: magyar szalas tea webshop, minosegi szalas teak es kiegeszitok, ingyenes szallitas 48.990 Ft felett.
Termekek: fekete teak, zold teak, gyumolcs teak, gyogynovenyes teak, ulongok, puerhok, matcha, tea kiegeszitok (bogrek, szurok, kannicskas).
Mindig magyarul valaszolj, legyen udvarias, baratsagos, rovid. Ha termeket keresel, iranitsd a felhasznalot a kategoriak fele.
Arrol amit nem tudsz biztosan, mondd hogy erdeklodjon a bolt ugyfelszolgalatat az info@yummatea.hu cimen.
NE talalj ki termekeket, arakat, keszletinformaciot — csak azt mondd amit biztosan tudsz.";
}

// --- Frontend widget ---
add_action('wp_footer', 'yumma_chat_render_widget');

function yumma_chat_render_widget() {
    if (!get_option('yumma_chat_api_key')) return;
    $position = get_option('yumma_chat_position', 'bottom-right');
    $pos_css  = $position === 'bottom-left' ? 'left:24px;' : 'right:24px;';
    ?>
    <style>
    #yumma-chat-btn{position:fixed;bottom:24px;<?php echo $pos_css ?>z-index:9999;background:#5c8a3c;color:#fff;border:none;border-radius:50%;width:56px;height:56px;font-size:26px;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.2);transition:background .2s;}
    #yumma-chat-btn:hover{background:#4a7030;}
    #yumma-chat-box{display:none;position:fixed;bottom:92px;<?php echo $pos_css ?>z-index:9999;width:340px;max-height:520px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.18);flex-direction:column;font-family:'Segoe UI',sans-serif;overflow:hidden;}
    #yumma-chat-box.open{display:flex;}
    #yumma-chat-head{background:#5c8a3c;color:#fff;padding:14px 16px;display:flex;align-items:center;gap:10px;}
    #yumma-chat-head img{width:32px;height:32px;border-radius:50%;background:#fff;padding:2px;}
    #yumma-chat-head span{font-weight:600;font-size:.97rem;}
    #yumma-chat-head small{font-size:.75rem;opacity:.85;display:block;}
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

    <button id="yumma-chat-btn" title="YUMMA AI Asszisztens">🍵</button>

    <div id="yumma-chat-box">
        <div id="yumma-chat-head">
            <span>🍵</span>
            <div><span>YUMMA Asszisztens</span><small>Segitunk a teavalasztasban!</small></div>
            <button id="yumma-chat-close">✕</button>
        </div>
        <div id="yumma-chat-msgs">
            <div class="yc-msg bot">Szia! A YUMMA tea asszisztense vagyok. 🍵 Segitsek teat talalni, vagy kerdezz a rendelessel kapcsolatban!</div>
        </div>
        <div id="yumma-chat-foot">
            <textarea id="yumma-chat-input" rows="2" placeholder="Irj uzzenetet..."></textarea>
            <button id="yumma-chat-send">&#10148;</button>
        </div>
    </div>

    <script>
    (function(){
        const btn=document.getElementById('yumma-chat-btn');
        const box=document.getElementById('yumma-chat-box');
        const closeBtn=document.getElementById('yumma-chat-close');
        const msgs=document.getElementById('yumma-chat-msgs');
        const inp=document.getElementById('yumma-chat-input');
        const send=document.getElementById('yumma-chat-send');
        const API='<?php echo esc_js(rest_url('yumma-chat/v1/message')); ?>';
        const history=[];

        btn.addEventListener('click',()=>box.classList.toggle('open'));
        closeBtn.addEventListener('click',()=>box.classList.remove('open'));

        function addMsg(role,text,cls=''){
            const d=document.createElement('div');
            d.className='yc-msg '+(cls||role);
            d.textContent=text;
            msgs.appendChild(d);
            msgs.scrollTop=msgs.scrollHeight;
            return d;
        }

        async function sendMsg(){
            const text=inp.value.trim();
            if(!text)return;
            inp.value='';send.disabled=true;
            history.push({role:'user',content:text});
            addMsg('user',text);
            const t=addMsg('bot','Gondolkodom...','thinking');
            try{
                const r=await fetch(API,{
                    method:'POST',
                    headers:{'Content-Type':'application/json','X-WP-Nonce':'<?php echo wp_create_nonce('wp_rest'); ?>'},
                    body:JSON.stringify({messages:history})
                });
                const d=await r.json();
                t.remove();
                const reply=d.reply||'Sajnalom, hiba tortent.';
                history.push({role:'assistant',content:reply});
                addMsg('bot',reply);
            }catch(e){
                t.remove();
                addMsg('bot','Kapcsolodasi hiba. Kerlek probald ujra.');
            }
            send.disabled=false;inp.focus();
        }

        send.addEventListener('click',sendMsg);
        inp.addEventListener('keydown',e=>{
            if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendMsg();}
        });
    })();
    </script>
    <?php
}
