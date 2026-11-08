<?php
// includes/footer.php
if (!function_exists('__')) {
    require_once __DIR__ . '/lang.php';
}
?>

<!-- ─── Floating AI Chatbot Widget ─── -->
<style>
/* Chatbot bubble */
#chatbotBubble {
  position: fixed;
  bottom: 28px;
  right: 28px;
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary, #6c63ff), #a78bfa);
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 6px 24px rgba(108,99,255,.45);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  z-index: 9999;
  transition: transform .2s, box-shadow .2s;
}
#chatbotBubble:hover { transform: scale(1.1); box-shadow: 0 10px 32px rgba(108,99,255,.6); }
#chatbotBubble .notif-dot {
  position: absolute;
  top: 4px; right: 4px;
  width: 12px; height: 12px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid #fff;
  display: none;
}

/* Chatbot panel */
#chatbotPanel {
  position: fixed;
  bottom: 96px;
  right: 28px;
  width: 360px;
  max-height: 520px;
  background: var(--bg-2, #1e1e2e);
  border: 1px solid var(--border, rgba(255,255,255,.08));
  border-radius: 18px;
  box-shadow: 0 16px 48px rgba(0,0,0,.45);
  display: flex;
  flex-direction: column;
  z-index: 9998;
  opacity: 0;
  transform: translateY(20px) scale(.96);
  pointer-events: none;
  transition: opacity .25s, transform .25s;
}
#chatbotPanel.open {
  opacity: 1;
  transform: translateY(0) scale(1);
  pointer-events: all;
}

/* Panel header */
.cb-header {
  padding: 14px 18px;
  background: linear-gradient(135deg, var(--primary, #6c63ff), #a78bfa);
  border-radius: 18px 18px 0 0;
  display: flex;
  align-items: center;
  gap: 12px;
}
.cb-header .cb-avatar {
  width: 38px; height: 38px;
  background: rgba(255,255,255,.2);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; color: #fff;
  flex-shrink: 0;
}
.cb-header-text { flex: 1; color: #fff; }
.cb-header-text strong { display: block; font-size: .95rem; }
.cb-header-text span  { font-size: .75rem; opacity: .85; }
.cb-close {
  background: rgba(255,255,255,.15);
  border: none;
  color: #fff;
  width: 28px; height: 28px;
  border-radius: 50%;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem;
  transition: background .2s;
}
.cb-close:hover { background: rgba(255,255,255,.3); }

/* Messages area */
.cb-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-height: 340px;
}
.cb-messages::-webkit-scrollbar { width: 4px; }
.cb-messages::-webkit-scrollbar-thumb { background: var(--border, rgba(255,255,255,.1)); border-radius: 4px; }

.cb-msg {
  display: flex;
  gap: 8px;
  animation: cbFadeIn .2s ease;
}
@keyframes cbFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

.cb-msg.user { flex-direction: row-reverse; }

.cb-bubble {
  max-width: 80%;
  padding: 10px 14px;
  border-radius: 16px;
  font-size: .875rem;
  line-height: 1.5;
}
.cb-msg.bot  .cb-bubble {
  background: var(--bg-3, rgba(255,255,255,.06));
  color: var(--text-1, #f1f5f9);
  border-bottom-left-radius: 4px;
}
.cb-msg.user .cb-bubble {
  background: linear-gradient(135deg, var(--primary, #6c63ff), #a78bfa);
  color: #fff;
  border-bottom-right-radius: 4px;
}
.cb-msg .cb-icon {
  width: 28px; height: 28px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem;
  flex-shrink: 0;
  margin-top: auto;
}
.cb-msg.bot  .cb-icon { background: linear-gradient(135deg, var(--primary, #6c63ff), #a78bfa); color: #fff; }
.cb-msg.user .cb-icon { background: var(--bg-3, rgba(255,255,255,.1)); color: var(--text-2, #94a3b8); }

/* Typing indicator */
.cb-typing .cb-bubble { display: flex; align-items: center; gap: 4px; padding: 12px 16px; }
.cb-typing .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--text-3, #64748b); animation: cbDot 1.2s infinite ease-in-out; }
.cb-typing .dot:nth-child(2) { animation-delay: .2s; }
.cb-typing .dot:nth-child(3) { animation-delay: .4s; }
@keyframes cbDot { 0%,80%,100% { transform: scale(.6); opacity:.4; } 40% { transform: scale(1); opacity:1; } }

/* Input bar */
.cb-inputbar {
  padding: 12px 14px;
  border-top: 1px solid var(--border, rgba(255,255,255,.08));
  display: flex;
  gap: 8px;
  border-radius: 0 0 18px 18px;
  background: var(--bg-2, #1e1e2e);
}
.cb-inputbar input {
  flex: 1;
  background: var(--bg-3, rgba(255,255,255,.06));
  border: 1px solid var(--border, rgba(255,255,255,.1));
  border-radius: 10px;
  padding: 9px 13px;
  color: var(--text-1, #f1f5f9);
  font-size: .875rem;
  outline: none;
  transition: border-color .2s;
}
.cb-inputbar input:focus { border-color: var(--primary, #6c63ff); }
.cb-inputbar input::placeholder { color: var(--text-3, #64748b); }
.cb-send {
  width: 38px; height: 38px;
  background: linear-gradient(135deg, var(--primary, #6c63ff), #a78bfa);
  border: none;
  border-radius: 10px;
  color: #fff;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: .9rem;
  transition: opacity .2s, transform .1s;
  flex-shrink: 0;
}
.cb-send:hover { opacity: .88; }
.cb-send:active { transform: scale(.93); }
.cb-send:disabled { opacity: .4; cursor: not-allowed; }

@media (max-width: 480px) {
  #chatbotPanel { width: calc(100vw - 24px); right: 12px; bottom: 88px; }
  #chatbotBubble { right: 16px; bottom: 20px; }
}
</style>

<!-- Bubble trigger -->
<button id="chatbotBubble" onclick="toggleChatbot()" aria-label="Open AI Assistant">
  <i class="fas fa-robot"></i>
  <span class="notif-dot" id="cbDot"></span>
</button>

<!-- Chat panel -->
<div id="chatbotPanel">
  <div class="cb-header">
    <div class="cb-avatar"><i class="fas fa-robot"></i></div>
    <div class="cb-header-text">
      <strong><?php echo h(__('chat_title')); ?></strong>
      <span><?php echo h(__('chat_subtitle')); ?></span>
    </div>
    <button class="cb-close" onclick="toggleChatbot()"><i class="fas fa-times"></i></button>
  </div>

  <div class="cb-messages" id="cbMessages">
    <!-- Welcome message injected by JS -->
  </div>

  <div class="cb-inputbar">
    <input type="text" id="cbInput"
           placeholder="<?php echo h(__('chat_placeholder')); ?>"
           onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); sendChatMessage(); }">
    <button class="cb-send" id="cbSend" onclick="sendChatMessage()">
      <i class="fas fa-paper-plane"></i>
    </button>
  </div>
</div>

<script>
(function() {
  const WELCOME = <?php echo json_encode(h(__('chat_welcome'))); ?>;
  const T_ERROR = <?php echo json_encode(h(__('chat_error'))); ?>;
  const T_THINK = <?php echo json_encode(h(__('chat_thinking'))); ?>;

  let chatOpen    = false;
  let chatHistory = []; // [{role:'user'|'model', text:''}]
  let initialized = false;

  window.toggleChatbot = function() {
    chatOpen = !chatOpen;
    document.getElementById('chatbotPanel').classList.toggle('open', chatOpen);
    document.getElementById('cbDot').style.display = 'none';

    if (chatOpen && !initialized) {
      initialized = true;
      appendMsg('bot', WELCOME);
      document.getElementById('cbInput').focus();
    }
    if (chatOpen) document.getElementById('cbInput').focus();
  };

  window.sendChatMessage = async function() {
    const input = document.getElementById('cbInput');
    const send  = document.getElementById('cbSend');
    const text  = input.value.trim();
    if (!text) return;

    input.value = '';
    send.disabled = true;

    appendMsg('user', text);
    chatHistory.push({ role: 'user', text });

    const typingId = appendTyping();

    try {
      const res = await fetch('/olx1/ajax/chatbot.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text, history: chatHistory.slice(-10) })
      });
      const data = await res.json();
      removeTyping(typingId);

      const reply = data.reply || data.error || T_ERROR;
      appendMsg('bot', reply);
      chatHistory.push({ role: 'model', text: reply });
    } catch(e) {
      removeTyping(typingId);
      appendMsg('bot', T_ERROR);
    }

    send.disabled = false;
    input.focus();
  };

  function appendMsg(role, text) {
    const wrap = document.getElementById('cbMessages');
    const div  = document.createElement('div');
    div.className = 'cb-msg ' + role;

    const icon = document.createElement('div');
    icon.className = 'cb-icon';
    icon.innerHTML = role === 'bot'
      ? '<i class="fas fa-robot"></i>'
      : '<i class="fas fa-user"></i>';

    const bubble = document.createElement('div');
    bubble.className = 'cb-bubble';
    bubble.innerHTML = text.replace(/\n/g, '<br>').replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    div.appendChild(icon);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
    return div;
  }

  function appendTyping() {
    const wrap = document.getElementById('cbMessages');
    const div  = document.createElement('div');
    div.className = 'cb-msg bot cb-typing';
    div.id = 'cbTyping_' + Date.now();
    const icon   = document.createElement('div');
    icon.className = 'cb-icon';
    icon.innerHTML = '<i class="fas fa-robot"></i>';
    const bubble = document.createElement('div');
    bubble.className = 'cb-bubble';
    bubble.innerHTML = '<div class="dot"></div><div class="dot"></div><div class="dot"></div>';
    div.appendChild(icon);
    div.appendChild(bubble);
    wrap.appendChild(div);
    wrap.scrollTop = wrap.scrollHeight;
    return div.id;
  }

  function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  // Show dot after 3s to nudge user to open chat
  setTimeout(() => {
    if (!chatOpen) document.getElementById('cbDot').style.display = 'block';
  }, 3000);
})();
</script>
<footer class="main-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo">
          <span class="logo-main">OLX</span><span class="logo-plus">Plus</span>
        </div>
        <p><?php echo h(__('footer_about')); ?></p>
      </div>
      <div class="footer-col">
        <h4><?php echo h(__('footer_marketplace')); ?></h4>
        <a href="/olx1/index.php"><?php echo h(__('home')); ?></a>
        <a href="/olx1/search.php"><?php echo h(__('footer_browse_all')); ?></a>
        <a href="/olx1/search.php?online=1"><?php echo h(__('footer_online_del')); ?></a>
        <a href="/olx1/post-ad.php"><?php echo h(__('footer_post_ad')); ?></a>
      </div>
      <div class="footer-col">
        <h4><?php echo h(__('footer_account')); ?></h4>
        <a href="/olx1/dashboard.php"><?php echo h(__('footer_dashboard')); ?></a>
        <a href="/olx1/chat.php"><?php echo h(__('footer_messages')); ?></a>
        <a href="/olx1/dashboard.php?tab=receipts"><?php echo h(__('footer_receipts')); ?></a>
        <a href="/olx1/dashboard.php?tab=profile"><?php echo h(__('footer_profile')); ?></a>
      </div>
      <div class="footer-col">
        <h4><?php echo h(__('footer_safety')); ?></h4>
        <a href="#"><?php echo h(__('footer_safety_tips')); ?></a>
        <a href="#"><?php echo h(__('footer_report_scam')); ?></a>
        <a href="#"><?php echo h(__('footer_how_receipts')); ?></a>
        <a href="#"><?php echo h(__('footer_terms')); ?></a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?php echo date('Y'); ?> OLX Plus. <?php echo h(__('footer_rights')); ?></span>
      <span style="color:var(--text-3);"><?php echo h(__('footer_trust')); ?> <i class="fas fa-shield-alt" style="color:var(--primary);"></i></span>
    </div>
  </div>
</footer>
<?php include 'cookie-banner.php'; ?>
