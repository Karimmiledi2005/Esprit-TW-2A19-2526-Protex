/**
 * chatbot-assurance.js
 * Widget chatbot IA — Domaine Assurance Protex uniquement
 * ────────────────────────────────────────────────────────
 * v2.0 — Dark mode, historique localStorage, feedback
 */
(function () {
  'use strict';

  var EMAIL    = window.PROTEX_EMAIL || '';
  var API_URL  = 'chatbot.php';
  var isOpen   = false;
  var isTyping = false;
  var STORAGE_KEY = 'protex_chat_history';

  /* ── Pré-filtre client : mots-clés hors domaine ─────────────── */
  var OFF_TOPIC = [
    'météo','sport','foot','football','film','cinéma','musique',
    'cuisine','recette','politique','jeux','blague','histoire',
    'géographie','math','mathématiques','programmation','code',
    'javascript','python','java','voyage','hotel','restaurant',
    'shopping','mode','actualité','news','people'
  ];
  function isOffTopic(msg) {
    var m = msg.toLowerCase().replace(/[^a-zà-ÿ0-9\s]/g, '');
    return OFF_TOPIC.some(function (kw) { return m.indexOf(kw) !== -1; });
  }

  /* ── CSS complet du panneau (supports dark mode) ──────────── */
  var isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  var CSS = `
    #chat-overlay {
      position:fixed; inset:0; z-index:8000;
      background:rgba(15,25,50,.50); backdrop-filter:blur(4px);
      opacity:0; pointer-events:none; transition:opacity .3s;
    }
    #chat-overlay.open { opacity:1; pointer-events:all; }

    #chat-panel {
      position:fixed; top:0; right:0; bottom:0; z-index:8001;
      width:420px; max-width:100vw;
      background:${isDark ? '#1a1a2e' : '#fff'}; display:flex; flex-direction:column;
      box-shadow:-12px 0 50px rgba(31,47,77,.20);
      transform:translateX(100%);
      transition:transform .32s cubic-bezier(.4,0,.2,1);
    }
    #chat-panel.open { transform:translateX(0); }

    /* ── Header ── */
    #chat-head {
      flex-shrink:0;
      background:linear-gradient(135deg,#23458f 0%,#1d3c82 100%);
      padding:16px 18px; display:flex; align-items:center; gap:12px;
    }
    .chat-logo {
      width:40px; height:40px; border-radius:12px; flex-shrink:0;
      background:linear-gradient(135deg,#ff7a1a,#ef6b0a);
      display:flex; align-items:center; justify-content:center;
      font-size:18px; color:#fff; font-weight:900;
      box-shadow:0 4px 14px rgba(239,107,10,.40);
    }
    .chat-head-info { flex:1; min-width:0; }
    .chat-head-name { font-size:14px; font-weight:800; color:#fff; line-height:1.2; }
    .chat-head-sub  { font-size:11px; color:rgba(255,255,255,.65); margin-top:2px; display:flex; align-items:center; gap:5px; }
    .chat-online-dot { width:6px; height:6px; border-radius:50%; background:#22c55e; animation:onlinePulse 2s ease-in-out infinite; }
    @keyframes onlinePulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    #chat-status { color:rgba(255,255,255,.70); font-size:11px; }

    #chat-head-actions { display:flex; gap:4px; }
    #chat-head-actions button {
      width:30px; height:30px; border-radius:8px; border:none;
      background:rgba(255,255,255,.10); color:rgba(255,255,255,.7); cursor:pointer;
      display:flex; align-items:center; justify-content:center; font-size:14px;
      transition:background .2s;
    }
    #chat-head-actions button:hover { background:rgba(255,255,255,.20); }

    /* ── Badge domaine ── */
    .chat-domain-badge {
      flex-shrink:0; margin:10px 14px 0;
      background:${isDark ? 'rgba(99,102,241,.10)' : 'rgba(35,69,143,.07)'};
      border:1px solid ${isDark ? 'rgba(99,102,241,.20)' : 'rgba(35,69,143,.15)'};
      border-radius:10px; padding:8px 12px;
      display:flex; align-items:center; gap:7px;
      font-size:12px; color:${isDark ? '#a5b4fc' : '#23458f'}; font-weight:600;
    }

    /* ── Messages ── */
    #chat-msgs {
      flex:1; overflow-y:auto; padding:12px 14px 6px;
      display:flex; flex-direction:column; gap:10px; scroll-behavior:smooth;
    }
    #chat-msgs::-webkit-scrollbar { width:4px; }
    #chat-msgs::-webkit-scrollbar-thumb { background:${isDark ? '#2d2d4a' : '#dde5f0'}; border-radius:4px; }

    .cmsg { display:flex; gap:8px; animation:msgIn .22s ease; }
    @keyframes msgIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .cmsg.bot  { align-items:flex-end; }
    .cmsg.user { flex-direction:row-reverse; align-items:flex-end; }
    .cmsg-avatar {
      width:28px; height:28px; border-radius:50%; flex-shrink:0;
      background:linear-gradient(135deg,#23458f,#1d3c82);
      display:flex; align-items:center; justify-content:center;
      font-size:11px; font-weight:800; color:#fff;
    }
    .cmsg-bubble {
      max-width:82%; padding:10px 14px; border-radius:16px;
      font-size:13px; line-height:1.55; word-break:break-word;
    }
    .cmsg.bot  .cmsg-bubble { background:${isDark ? '#2d2d4a' : '#f2f5fb'}; color:${isDark ? '#e2e8f0' : '#1f2f4d'}; border-bottom-left-radius:4px; }
    .cmsg.user .cmsg-bubble { background:linear-gradient(135deg,#23458f,#1d3c82); color:#fff; border-bottom-right-radius:4px; }
    .cmsg-bubble.hors-domaine { background:${isDark ? '#3b1a1a' : '#fff5f5'}; color:${isDark ? '#fca5a5' : '#b91c1c'}; border:1px solid rgba(239,68,68,.2); }
    .cmsg-bubble.msg-erreur   { background:${isDark ? '#3b2e14' : '#fffbeb'}; color:${isDark ? '#fbbf24' : '#92400e'}; border:1px solid rgba(245,158,11,.25); font-size:12px; }
    .cmsg-time { font-size:10px; color:${isDark ? '#6b7280' : '#9aa7bd'}; margin-top:3px; text-align:right; }

    /* ── Feedback buttons ── */
    .cmsg-feedback { display:flex; gap:4px; margin-top:3px; justify-content:flex-end; }
    .cmsg-feedback button {
      background:none; border:none; cursor:pointer; font-size:12px;
      opacity:.35; transition:opacity .15s; padding:2px 4px;
    }
    .cmsg-feedback button:hover { opacity:.8; }

    /* ── Typing ── */
    .ctyping { display:flex; gap:5px; align-items:center; padding:10px 14px; }
    .ctyping span { width:6px; height:6px; border-radius:50%; background:${isDark ? '#6b7280' : '#9aa7bd'}; animation:cDot 1.2s ease-in-out infinite; }
    .ctyping span:nth-child(2){animation-delay:.2s}
    .ctyping span:nth-child(3){animation-delay:.4s}
    @keyframes cDot { 0%,80%,100%{transform:scale(.75);opacity:.45} 40%{transform:scale(1.1);opacity:1} }

    /* ── Questions rapides ── */
    .chat-quick-wrap { flex-shrink:0; padding:6px 14px 8px; display:flex; flex-wrap:wrap; gap:6px; }
    .chat-quick {
      font-size:11.5px; padding:5px 12px; border-radius:18px;
      border:1.5px solid ${isDark ? '#374151' : '#e0e7f0'};
      background:${isDark ? '#1f2937' : '#f8fafd'};
      color:${isDark ? '#93c5fd' : '#23458f'}; cursor:pointer; font-weight:600;
      transition:all .18s; white-space:nowrap;
    }
    .chat-quick:hover { background:#fff0e5; border-color:#ff7a1a; color:#ef6b0a; }

    /* ── Footer ── */
    #chat-footer {
      flex-shrink:0; padding:10px 12px 12px;
      border-top:1px solid ${isDark ? '#2d2d4a' : '#edf2f8'}; display:flex; gap:8px; align-items:flex-end;
    }
    #chat-input {
      flex:1; border:1.5px solid ${isDark ? '#374151' : '#e0e7f0'}; border-radius:12px;
      padding:9px 12px; font-size:13px; color:${isDark ? '#e2e8f0' : '#1f2f4d'};
      resize:none; outline:none; max-height:100px; min-height:38px;
      font-family:inherit; line-height:1.5; background:${isDark ? '#1f2937' : '#f8fafd'};
      transition:border-color .2s, background .2s;
    }
    #chat-input:focus { border-color:#23458f; background:${isDark ? '#1e293b' : '#fff'}; }
    #chat-input::placeholder { color:${isDark ? '#6b7280' : '#b0bac8'}; }
    #chat-send {
      width:38px; height:38px; border-radius:50%; border:none; flex-shrink:0;
      background:linear-gradient(135deg,#23458f,#1d3c82); color:#fff; cursor:pointer;
      display:flex; align-items:center; justify-content:center;
      transition:opacity .2s, transform .2s; box-shadow:0 3px 10px rgba(35,69,143,.35);
    }
    #chat-send:hover:not(:disabled) { opacity:.88; transform:scale(1.07); }
    #chat-send:disabled { opacity:.38; cursor:not-allowed; box-shadow:none; }
    #chat-send svg { width:16px; height:16px; fill:none; stroke:#fff; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    #chat-powered {
      flex-shrink:0; text-align:center; font-size:10px;
      color:${isDark ? '#6b7280' : '#b0bac8'}; padding-bottom:8px; letter-spacing:.3px;
    }

    /* ── Nouvelle conversation ── */
    #chat-new-btn {
      flex-shrink:0; text-align:center; font-size:11px;
      color:${isDark ? '#6366f1' : '#4f46e5'}; cursor:pointer;
      padding:4px 0 6px; text-decoration:none; font-weight:500;
      transition:color .2s;
    }
    #chat-new-btn:hover { color:#ff7a1a; }
  `;

  /* ── Questions rapides ─────────────────────────────────────── */
  var QUICK = [
    'Statut de mes réclamations',
    'Y a-t-il une réponse à ma réclamation ?',
    'Qu\'est-ce qu\'une franchise ?',
    'Comment fonctionne l\'assurance habitation ?',
    'Comment déclarer un sinistre auto ?',
    'Que couvre l\'assurance santé ?',
    'Délai de traitement d\'une réclamation ?',
    'Quels sont mes droits en tant qu\'assuré ?',
  ];

  /* ── Historique localStorage ──────────────────────────────── */
  function saveHistory() {
    var msgs = [];
    document.querySelectorAll('#chat-msgs .cmsg').forEach(function (el) {
      var bubble = el.querySelector('.cmsg-bubble');
      var isBot = el.classList.contains('bot');
      if (bubble && !bubble.classList.contains('ctyping')) {
        msgs.push({
          role: isBot ? 'bot' : 'user',
          text: bubble.innerText || bubble.textContent,
          time: el.querySelector('.cmsg-time')?.textContent || ''
        });
      }
    });
    if (msgs.length > 2) {
      try { localStorage.setItem(STORAGE_KEY + '_' + EMAIL, JSON.stringify(msgs.slice(-30))); } catch(e) {}
    }
  }

  function loadHistory() {
    try {
      var data = localStorage.getItem(STORAGE_KEY + '_' + EMAIL);
      if (!data) return false;
      var msgs = JSON.parse(data);
      if (!msgs.length) return false;
      msgs.forEach(function (m) {
        if (m.role === 'bot') addBot(m.text, false, false, true);
        else addUser(m.text, true);
      });
      return true;
    } catch(e) { return false; }
  }

  function clearHistory() {
    try { localStorage.removeItem(STORAGE_KEY + '_' + EMAIL); } catch(e) {}
  }

  /* ── Construire le DOM ────────────────────────────────────── */
  function build() {
    var s = document.createElement('style');
    s.textContent = CSS;
    document.head.appendChild(s);

    var overlay = document.createElement('div');
    overlay.id = 'chat-overlay';
    overlay.onclick = closeChat;
    document.body.appendChild(overlay);

    var panel = document.createElement('div');
    panel.id = 'chat-panel';
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', 'Assistant IA Assurance Protex');
    panel.innerHTML =
      '<div id="chat-head">' +
        '<div class="chat-logo">P</div>' +
        '<div class="chat-head-info">' +
          '<div class="chat-head-name">Assistant IA Protex</div>' +
          '<div class="chat-head-sub"><span class="chat-online-dot"></span><span id="chat-status">Spécialisé assurance · En ligne</span></div>' +
        '</div>' +
        '<div id="chat-head-actions">' +
          '<button id="chat-new-btn-head" title="Nouvelle conversation" aria-label="Nouvelle conversation">↺</button>' +
          '<button id="chat-close-btn" aria-label="Fermer">&#x2715;</button>' +
        '</div>' +
      '</div>' +
      '<div class="chat-domain-badge">🛡 Assurance Protex &amp; Questions générales</div>' +
      '<div id="chat-msgs"></div>' +
      '<div class="chat-quick-wrap" id="chat-quick"></div>' +
      '<div id="chat-footer">' +
        '<textarea id="chat-input" placeholder="Ex : Statut de ma réclamation ?" rows="1" maxlength="500"></textarea>' +
        '<button id="chat-send" aria-label="Envoyer"><svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>' +
      '</div>' +
      '<div id="chat-powered">Propulsé par Groq IA · Protex Assurance</div>';
    document.body.appendChild(panel);

    /* Questions rapides */
    var qc = document.getElementById('chat-quick');
    QUICK.forEach(function (q) {
      var btn = document.createElement('button');
      btn.className = 'chat-quick';
      btn.textContent = q;
      btn.onclick = function () { sendMsg(q); };
      qc.appendChild(btn);
    });

    /* Restaurer historique ou message d'accueil */
    var hasHistory = loadHistory();
    if (!hasHistory) {
      addBot(
        'Bonjour ! Je suis l\'assistant IA de Protex Assurance.\n\n' +
        'Je suis spécialisé dans :\n' +
        '• Vos réclamations (statut, suivi, réponses)\n' +
        '• Les sinistres (auto, santé, habitation)\n' +
        '• Vos contrats et garanties\n' +
        '• Les procédures et délais\n\n' +
        'Comment puis-je vous aider ?'
      );
    }

    /* Événements */
    document.getElementById('chat-close-btn').addEventListener('click', closeChat);
    document.getElementById('chat-send').addEventListener('click', onSend);
    document.getElementById('chat-input').addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); onSend(); }
    });
    document.getElementById('chat-input').addEventListener('input', autoResize);
    document.getElementById('chat-new-btn-head').addEventListener('click', newConversation);

    /* Lier le bouton de la page */
    var btn = document.getElementById('btnOpenChat');
    if (btn) btn.addEventListener('click', openChat);
  }

  /* ── Nouvelle conversation ──────────────────────────────── */
  function newConversation() {
    if (!confirm('Voulez-vous effacer l\'historique de cette conversation ?')) return;
    clearHistory();
    document.getElementById('chat-msgs').innerHTML = '';
    document.getElementById('chat-quick').style.display = 'flex';
    addBot('Conversation réinitialisée. Comment puis-je vous aider ?');
  }

  /* ── Open / Close ────────────────────────────────────────── */
  function openChat() {
    isOpen = true;
    document.getElementById('chat-overlay').classList.add('open');
    document.getElementById('chat-panel').classList.add('open');
    setTimeout(function () { document.getElementById('chat-input').focus(); }, 340);
  }
  function closeChat() {
    isOpen = false;
    document.getElementById('chat-overlay').classList.remove('open');
    document.getElementById('chat-panel').classList.remove('open');
    saveHistory();
  }

  /* ── Envoi ──────────────────────────────────────────────── */
  function onSend() {
    var input = document.getElementById('chat-input');
    var msg   = input.value.trim();
    if (!msg || isTyping) return;
    input.value = ''; autoResize.call(input);
    sendMsg(msg);
  }

  function sendMsg(msg) {
    addUser(msg);
    document.getElementById('chat-quick').style.display = 'none';

    /* Pré-filtre côté client */
    if (isOffTopic(msg)) {
      addBot(
        'Je suis spécialisé dans le domaine de l\'assurance ' +
        '(réclamations, sinistres, contrats, garanties, types d\'assurance, droits des assurés…).\n\n' +
        'Je ne peux pas répondre à cette question. Puis-je vous aider ' +
        'avec une question sur l\'assurance ou vos réclamations Protex ?',
        false, true
      );
      return;
    }

    showTyping(true);
    setStatus('En train d\'analyser…');

    fetch(API_URL, {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ message: msg, email: EMAIL })
    })
    .then(function (r) {
      if (!r.ok) {
         return r.json().then(function(errData) {
            throw new Error(errData.message || 'Erreur HTTP ' + r.status);
         }).catch(function() {
            throw new Error('Erreur HTTP ' + r.status);
         });
      }
      return r.json();
    })
    .then(function (data) {
      showTyping(false);
      setStatus('Spécialisé assurance · En ligne');
      if (data.success) {
        addBot(data.reply);
      } else {
        addBot(data.message || 'Une erreur est survenue. Veuillez réessayer.', false, true);
      }
    })
    .catch(function (err) {
      showTyping(false);
      setStatus('Spécialisé assurance · En ligne');
      addBot('🌐 Connexion au serveur impossible ou erreur : ' + err.message, false, true);
    });
  }

  /* ── Messages ──────────────────────────────────────────── */
  function addBot(text, horsdomaine, isError, noSave) {
    var box = document.getElementById('chat-msgs');
    var hm  = now();
    var cls = horsdomaine ? ' hors-domaine' : (isError ? ' msg-erreur' : '');
    var div = document.createElement('div');
    div.className = 'cmsg bot';
    div.innerHTML =
      '<div class="cmsg-avatar">P</div>' +
      '<div><div class="cmsg-bubble' + cls + '">' + escHtml(text) + '</div>' +
      '<div class="cmsg-time">' + hm + '</div>' +
      '<div class="cmsg-feedback">' +
        '<button onclick="void(0)" title="Utile">👍</button>' +
        '<button onclick="void(0)" title="Pas utile">👎</button>' +
      '</div></div>';
    box.appendChild(div); scrollBot();
    if (!noSave) saveHistory();
  }

  function addUser(text, noSave) {
    var box = document.getElementById('chat-msgs');
    var div = document.createElement('div');
    div.className = 'cmsg user';
    div.innerHTML =
      '<div><div class="cmsg-bubble">' + escHtml(text) + '</div>' +
      '<div class="cmsg-time">' + now() + '</div></div>';
    box.appendChild(div); scrollBot();
    if (!noSave) saveHistory();
  }

  /* ── Feedback ───────────────────────────────────────────── */
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.cmsg-feedback button');
    if (!btn) return;
    var parent = btn.closest('.cmsg');
    if (!parent) return;
    var isUp = btn.title === 'Utile';
    // Désactiver les deux boutons après feedback
    parent.querySelectorAll('.cmsg-feedback button').forEach(function(b) {
      b.style.opacity = '.2';
      b.style.cursor = 'default';
    });
    btn.style.opacity = '1';
    btn.textContent = isUp ? '👍' : '👎';
  });

  /* ── Typing indicator ──────────────────────────────────── */
  var typingEl = null;
  function showTyping(show) {
    isTyping = show;
    document.getElementById('chat-send').disabled = show;
    var box = document.getElementById('chat-msgs');
    if (show) {
      typingEl = document.createElement('div');
      typingEl.className = 'cmsg bot';
      typingEl.innerHTML =
        '<div class="cmsg-avatar">P</div>' +
        '<div class="cmsg-bubble ctyping"><span></span><span></span><span></span></div>';
      box.appendChild(typingEl); scrollBot();
    } else if (typingEl) { typingEl.remove(); typingEl = null; }
  }

  /* ── Helpers ───────────────────────────────────────────── */
  function now() {
    var d = new Date();
    return d.getHours() + ':' + (d.getMinutes() < 10 ? '0' : '') + d.getMinutes();
  }
  function scrollBot()  { var b = document.getElementById('chat-msgs'); b.scrollTop = b.scrollHeight; }
  function setStatus(t) { var el = document.getElementById('chat-status'); if (el) el.textContent = t; }
  function autoResize() { this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 100) + 'px'; }
  function escHtml(s) {
    return String(s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')
      .replace(/\n/g,'<br>');
  }

  /* ── Init ──────────────────────────────────────────────── */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', build);
  } else { build(); }

})();
