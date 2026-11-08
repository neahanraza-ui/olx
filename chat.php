<?php
// chat.php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

requireLogin();
validateSession($pdo);

$user_id    = getCurrentUserId();
$product_id = (int)($_GET['product_id'] ?? 0);
$conv_id    = (int)($_GET['conv_id'] ?? 0);

// Start conversation if product_id given
if ($product_id && !$conv_id) {
    $prod = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status='active'");
    $prod->execute([$product_id]);
    $prod = $prod->fetch();

    if ($prod && $prod['seller_id'] != $user_id) {
        $existing = $pdo->prepare("SELECT id FROM conversations WHERE product_id = ? AND buyer_id = ?");
        $existing->execute([$product_id, $user_id]);
        $existing = $existing->fetch();

        if ($existing) {
            $conv_id = $existing['id'];
        } else {
            $pdo->prepare("INSERT INTO conversations (product_id, buyer_id, seller_id) VALUES (?,?,?)")
                ->execute([$product_id, $user_id, $prod['seller_id']]);
            $conv_id = $pdo->lastInsertId();
        }
    }
}

// All conversations
$convs = $pdo->prepare("
  SELECT c.*, p.title AS product_title, p.is_online, p.price,
         pi.image_path AS product_img,
         u_other.name AS other_name,
         (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread
  FROM conversations c
  JOIN products p ON c.product_id = p.id
  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
  JOIN users u_other ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u_other.id
  WHERE c.buyer_id = ? OR c.seller_id = ?
  ORDER BY GREATEST(c.created_at, COALESCE(c.updated_at, c.created_at)) DESC
");
$convs->execute([$user_id, $user_id, $user_id, $user_id]);
$convs = $convs->fetchAll();

// Active conversation
$active_conv = null;
$messages    = [];
if ($conv_id) {
    $stmt = $pdo->prepare("
      SELECT c.*, p.title AS product_title, p.is_online, p.price, p.id AS product_id,
             pi.image_path AS product_img,
             u_other.name AS other_name, u_other.id AS other_id,
             u_seller.id AS seller_id
      FROM conversations c
      JOIN products p ON c.product_id = p.id
      LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
      JOIN users u_other ON (CASE WHEN c.buyer_id = ? THEN c.seller_id ELSE c.buyer_id END) = u_other.id
      JOIN users u_seller ON c.seller_id = u_seller.id
      WHERE c.id = ? AND (c.buyer_id = ? OR c.seller_id = ?)
    ");
    $stmt->execute([$user_id, $conv_id, $user_id, $user_id]);
    $active_conv = $stmt->fetch();

    if ($active_conv) {
        $msgs = $pdo->prepare("SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.created_at ASC");
        $msgs->execute([$conv_id]);
        $messages = $msgs->fetchAll();
        // Mark as read
        $pdo->prepare("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?")->execute([$conv_id, $user_id]);
    }
}

$is_seller = $active_conv && $active_conv['seller_id'] == $user_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages – OLX Plus</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="chat-layout">

  <!-- Conversation List Sidebar -->
  <div class="chat-sidebar-list">
    <div class="sidebar-head">
      <h3 style="font-size:1rem;">Messages</h3>
    </div>
    <div class="conv-list">
      <?php if (empty($convs)): ?>
        <div style="padding:32px;text-align:center;color:var(--text-3);font-size:.875rem;">No conversations yet.</div>
      <?php else: ?>
        <?php foreach ($convs as $c): ?>
          <a href="chat.php?conv_id=<?php echo $c['id']; ?>" class="conv-item <?php echo ($c['id'] == $conv_id) ? 'active' : ''; ?>">
            <div class="conv-avatar">
              <?php if ($c['product_img']): ?>
                <img src="<?php echo h($c['product_img']); ?>" alt="" style="width:44px;height:44px;border-radius:50%;object-fit:cover;">
              <?php else: ?>
                <i class="fas fa-box" style="font-size:1.1rem;"></i>
              <?php endif; ?>
            </div>
            <div class="conv-info">
              <div class="conv-name">
                <?php echo h($c['other_name']); ?>
                <?php if ($c['unread']): ?><span class="badge badge-online" style="margin-left:6px;font-size:.65rem;"><?php echo $c['unread']; ?></span><?php endif; ?>
              </div>
              <div class="conv-product">
                <?php if ($c['is_online']): ?><i class="fas fa-globe" style="color:var(--warning);font-size:.7rem;"></i> <?php endif; ?>
                <?php echo h($c['product_title']); ?>
              </div>
            </div>
            <div class="conv-time"><?php echo date('H:i', strtotime($c['updated_at'])); ?></div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat Window -->
  <?php if ($active_conv): ?>
  <div class="chat-window">

    <!-- Chat Header -->
    <div class="chat-header-bar">
      <div class="product-thumb">
        <img src="<?php echo $active_conv['product_img'] ? h($active_conv['product_img']) : 'assets/images/placeholder.webp'; ?>" alt="">
      </div>
      <div>
        <div class="chat-title">
          <?php echo h($active_conv['other_name']); ?>
          <?php if ($active_conv['is_online']): ?><span class="badge badge-online" style="margin-left:6px;">Online Listing</span><?php endif; ?>
        </div>
        <div class="chat-subtitle">
          <a href="product.php?id=<?php echo $active_conv['product_id']; ?>"><?php echo h($active_conv['product_title']); ?></a>
          · Rs <?php echo number_format($active_conv['price']); ?>
        </div>
      </div>
      <div class="chat-header-actions">
        <button class="btn btn-primary btn-sm" onclick="openAIChat(<?php echo $active_conv['product_id']; ?>)" title="Ask AI Helper"><i class="fas fa-robot"></i> AI Help</button>
        <a href="product.php?id=<?php echo $active_conv['product_id']; ?>" class="btn btn-ghost btn-sm" title="View Product"><i class="fas fa-external-link-alt"></i></a>
        
        <?php if ($active_conv['is_online']): ?>
          <?php if ($is_seller): ?>
            <button class="btn btn-warning btn-sm" onclick="confirmSale(<?php echo $active_conv['product_id']; ?>, 'seller_accept')">
              <i class="fas fa-check-double"></i> Confirm Sale
            </button>
          <?php else: ?>
            <button class="btn btn-success btn-sm" onclick="confirmSale(<?php echo $active_conv['product_id']; ?>, 'buyer_confirm')">
              <i class="fas fa-shopping-cart"></i> Buy Now
            </button>
          <?php endif; ?>
        <?php elseif (!$is_seller): ?>
          <button class="btn btn-success btn-sm" onclick="openDealModal()"><i class="fas fa-handshake"></i> Propose Deal</button>
        <?php endif; ?>

        <div class="dropdown" style="position:relative;">
            <button class="btn btn-ghost btn-sm" onclick="toggleChatMenu()"><i class="fas fa-ellipsis-v"></i></button>
            <div id="chatMenu" class="card" style="display:none; position:absolute; right:0; top:40px; z-index:100; min-width:150px; padding:8px;">
                <button class="sidebar-link" style="width:100%; border:none; background:none; text-align:left; color:var(--danger);" onclick="blockUser(<?php echo $active_conv['other_id']; ?>)">
                    <i class="fas fa-ban"></i> Block User
                </button>
                <button class="sidebar-link" style="width:100%; border:none; background:none; text-align:left;" onclick="reportChat(<?php echo $conv_id; ?>)">
                    <i class="fas fa-flag"></i> Report Chat
                </button>
            </div>
        </div>
      </div>
    </div>

    <!-- AI Chat Modal -->
    <div class="modal-overlay" id="aiModal">
      <div class="modal-box" style="max-width:550px; height:80vh; display:flex; flex-direction:column; padding:0; overflow:hidden;">
        <div class="chat-header-bar" style="border-radius:16px 16px 0 0;">
          <div class="stat-icon" style="background:var(--primary-dim); color:var(--primary);"><i class="fas fa-robot"></i></div>
          <div style="flex:1;">
            <div class="chat-title">OLX Plus AI Assistant</div>
            <div class="chat-subtitle">Powered by Gemini</div>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="document.getElementById('aiModal').classList.remove('open')"><i class="fas fa-times"></i></button>
        </div>
        <div class="chat-messages-area" id="aiMsgArea" style="flex:1; background:var(--bg);">
          <div class="msg recv">
            <div class="msg-bubble">Hello! I'm your OLX Plus Assistant. How can I help you with this listing?</div>
          </div>
        </div>
        <div class="chat-input-bar" style="border-radius:0 0 16px 16px;">
          <input type="text" id="aiInput" placeholder="Ask anything about this product…" onkeypress="if(event.key==='Enter') sendAIMsg()">
          <button class="chat-send-btn" onclick="sendAIMsg()"><i class="fas fa-paper-plane"></i></button>
        </div>
      </div>
    </div>

    <?php if ($active_conv['is_online']): ?>
      <div class="alert alert-warning" style="border-radius:0;border-left:none;border-right:none;border-top:none;">
        <i class="fas fa-exclamation-triangle"></i> <strong>Online listing</strong> — Do not send any advance payments. The platform does not guarantee online deliveries.
      </div>
    <?php endif; ?>

    <!-- Messages -->
    <div class="chat-messages-area" id="msgArea">
      <?php foreach ($messages as $msg): ?>
        <?php
          $type = $msg['is_system'] ? 'system' : ($msg['sender_id'] == $user_id ? 'sent' : 'recv');
        ?>
        <div class="msg <?php echo $type; ?>" id="msg-<?php echo $msg['id']; ?>">
          <div class="msg-bubble">
            <?php if (!empty($msg['image_path'])): ?>
              <div class="msg-image-wrap" style="margin-bottom:8px;">
                <img src="<?php echo h($msg['image_path']); ?>" style="max-width:100%; border-radius:8px; display:block; cursor:pointer;" onclick="window.open(this.src)">
              </div>
            <?php endif; ?>
            <?php if (!empty($msg['message'])): ?>
              <?php echo nl2br(h($msg['message'])); ?>
            <?php endif; ?>
          </div>
          <div class="msg-time">
            <?php if ($type !== 'system'): ?><?php echo h($msg['sender_name']); ?> · <?php endif; ?>
            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
            <?php if ($type === 'sent'): ?>
              <span class="read-receipt" style="margin-left:4px;">
                <i class="fas <?php echo $msg['is_read'] ? 'fa-check-double text-primary' : 'fa-check'; ?>" style="font-size:.7rem;"></i>
              </span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Input -->
    <div class="chat-input-bar">
      <input type="file" id="imageInput" style="display:none;" accept="image/*" onchange="uploadImage(this)">
      <button class="btn btn-ghost" onclick="document.getElementById('imageInput').click()" title="Send Image">
        <i class="fas fa-camera"></i>
      </button>
      <input type="text" id="msgInput" placeholder="Type a message…" onkeypress="if(event.key==='Enter') sendMsg()">
      <button class="chat-send-btn" onclick="sendMsg()"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>

  <!-- Deal Proposal Modal (Buyer only) -->
  <?php if (!$is_seller && !$active_conv['is_online']): ?>
  <div class="modal-overlay" id="dealModal">
    <div class="modal-box">
      <h2><i class="fas fa-handshake" style="color:var(--success);margin-right:8px;"></i> Propose a Deal</h2>
      <p style="color:var(--text-3);margin-bottom:20px;font-size:.9rem;">Suggest your final price and a meeting place. The seller can accept or reject.</p>
      <div class="form-group">
        <label>Agreed Price (Rs)</label>
        <input class="form-control" type="number" id="deal_price" value="<?php echo $active_conv['price']; ?>" min="0">
      </div>
      <div class="form-group">
        <label>Meeting Location</label>
        <input class="form-control" type="text" id="deal_location" placeholder="e.g. Liberty Market, Lahore">
      </div>
      <div class="modal-actions">
        <button class="btn btn-success" onclick="submitDeal()"><i class="fas fa-paper-plane"></i> Send Proposal</button>
        <button class="btn btn-ghost" onclick="document.getElementById('dealModal').classList.remove('open')">Cancel</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <!-- No conversation selected -->
  <div class="no-chat-selected">
    <i class="fas fa-comment-dots"></i>
    <p>Select a conversation to start chatting</p>
    <a href="search.php" class="btn btn-primary">Browse Listings</a>
  </div>
  <?php endif; ?>

</div>

<script>
const CONV_ID = <?php echo $conv_id ?: 0; ?>;
const USER_ID = <?php echo $user_id; ?>;
let lastId   = <?php echo !empty($messages) ? end($messages)['id'] : 0; ?>;

async function sendMsg() {
  const input = document.getElementById('msgInput');
  const msg   = input.value.trim();
  if (!msg || !CONV_ID) return;
  input.value = '';

  const fd = new FormData();
  fd.append('conversation_id', CONV_ID);
  fd.append('message', msg);

  const res  = await fetch('ajax/send-message.php', { method:'POST', body:fd });
  const data = await res.json();
  // We don't append here anymore, pollMessages will pick it up
}

async function uploadImage(input) {
  if (!input.files || !input.files[0]) return;
  
  const fd = new FormData();
  fd.append('image', input.files[0]);
  
  const uploadRes = await fetch('ajax/upload-chat-image.php', { method:'POST', body:fd });
  const uploadData = await uploadRes.json();
  
  if (uploadData.success) {
    const sendFd = new FormData();
    sendFd.append('conversation_id', CONV_ID);
    sendFd.append('image_path', uploadData.path);
    await fetch('ajax/send-message.php', { method:'POST', body:sendFd });
    input.value = '';
  } else {
    alert(uploadData.error || 'Upload failed');
  }
}

async function pollMessages() {
  if (!CONV_ID) return;
  const res  = await fetch(`ajax/get-messages.php?conversation_id=${CONV_ID}&last_id=${lastId}`);
  const data = await res.json();
  
  if (data.messages && data.messages.length) {
    data.messages.forEach(m => appendMsg(m));
  }
  
  if (data.read_statuses && data.read_statuses.length) {
    data.read_statuses.forEach(rs => {
      const msgDiv = document.getElementById(`msg-${rs.id}`);
      if (msgDiv && rs.is_read) {
        const receipt = msgDiv.querySelector('.read-receipt i');
        if (receipt) receipt.className = 'fas fa-check-double text-primary';
      }
    });
  }
}

function appendMsg(m) {
  if (m.id <= lastId) return;
  lastId = m.id;
  const area = document.getElementById('msgArea');
  const type = m.is_system ? 'system' : (m.sender_id == USER_ID ? 'sent' : 'recv');
  const div  = document.createElement('div');
  div.className = `msg ${type}`;
  div.id = `msg-${m.id}`;
  
  let content = '';
  if (m.image_path) {
    content += `<div class="msg-image-wrap" style="margin-bottom:8px;"><img src="${m.image_path}" style="max-width:100%; border-radius:8px; display:block; cursor:pointer;" onclick="window.open(this.src)"></div>`;
  }
  if (m.message) {
      content += `<div>${m.message.replace(/\n/g, '<br>')}</div>`;
  }
  
  const receipt = (type === 'sent') ? `<span class="read-receipt" style="margin-left:4px;"><i class="fas ${m.is_read == 1 ? 'fa-check-double text-primary' : 'fa-check'}" style="font-size:.7rem;"></i></span>` : '';
  
  div.innerHTML = `<div class="msg-bubble">${content}</div><div class="msg-time">${type !== 'system' ? m.sender_name + ' · ' : ''}${m.created_at} ${receipt}</div>`;
  area.appendChild(div);
  area.scrollTop = area.scrollHeight;
}

function toggleChatMenu() {
    const m = document.getElementById('chatMenu');
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
}

async function blockUser(id) {
    if (!confirm('Are you sure you want to block this user? You will no longer receive messages from them.')) return;
    const fd = new FormData();
    fd.append('blocked_id', id);
    fd.append('action', 'block');
    const res = await fetch('ajax/block-user.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        alert(data.message);
        window.location.reload();
    }
}

async function reportChat(id) {
    const reason = prompt('Please describe the issue with this chat:');
    if (!reason) return;
    const fd = new FormData();
    fd.append('conversation_id', id);
    fd.append('reason', reason);
    const res = await fetch('ajax/report-chat.php', { method: 'POST', body: fd });
    const data = await res.json();
    alert(data.message);
}

function openDealModal() { document.getElementById('dealModal').classList.add('open'); }

// AI Assistant
let aiProductId = 0;
function openAIChat(productId) {
    aiProductId = productId;
    document.getElementById('aiModal').classList.add('open');
}

async function sendAIMsg() {
    const input = document.getElementById('aiInput');
    const prompt = input.value.trim();
    if (!prompt) return;
    input.value = '';
    
    const area = document.getElementById('aiMsgArea');
    
    // Append user message
    const userMsg = document.createElement('div');
    userMsg.className = 'msg sent';
    userMsg.innerHTML = `<div class="msg-bubble">${prompt}</div>`;
    area.appendChild(userMsg);
    area.scrollTop = area.scrollHeight;
    
    // Append loading bubble
    const loading = document.createElement('div');
    loading.className = 'msg recv';
    loading.innerHTML = `<div class="msg-bubble"><i class="fas fa-circle-notch fa-spin"></i> thinking…</div>`;
    area.appendChild(loading);
    area.scrollTop = area.scrollHeight;
    
    const fd = new FormData();
    fd.append('prompt', prompt);
    if (aiProductId) fd.append('product_id', aiProductId);
    
    const res = await fetch('ajax/ai-assistant.php', { method: 'POST', body: fd });
    const data = await res.json();
    
    area.removeChild(loading);
    
    const aiMsg = document.createElement('div');
    aiMsg.className = 'msg recv';
    aiMsg.innerHTML = `<div class="msg-bubble">${data.message || 'Error communicating with AI.'}</div>`;
    area.appendChild(aiMsg);
    area.scrollTop = area.scrollHeight;
}

async function submitDeal() {
  const price = document.getElementById('deal_price').value;
  const loc   = document.getElementById('deal_location').value;
  if (!price || !loc) { alert('Fill in both fields.'); return; }

  const fd = new FormData();
  fd.append('product_id', '<?php echo $active_conv['product_id'] ?? 0; ?>');
  fd.append('agreed_price', price);
  fd.append('meeting_location', loc);

  const res  = await fetch('ajax/propose-deal.php', { method:'POST', body:fd });
  const data = await res.json();
  if (data.success) { document.getElementById('dealModal').classList.remove('open'); pollMessages(); }
}

async function confirmSale(productId, action) {
    if (!confirm(action === 'buyer_confirm' ? 'Initiate purchase for this product?' : 'Confirm sale and generate receipt?')) return;
    
    const fd = new FormData();
    fd.append('product_id', productId);
    fd.append('action', action);
    
    const res = await fetch('ajax/confirm-sale.php', { method: 'POST', body: fd });
    const data = await res.json();
    
    if (data.success) {
        alert(data.message);
        pollMessages();
        if (data.status === 'confirmed') {
            window.location.href = 'receipt.php?product_id=' + productId;
        }
    } else {
        alert(data.error || 'Operation failed');
    }
}

// Scroll to bottom on load
const area = document.getElementById('msgArea');
if (area) area.scrollTop = area.scrollHeight;

// Poll every 3 seconds
if (CONV_ID) setInterval(pollMessages, 3000);
</script>
</body>
</html>
