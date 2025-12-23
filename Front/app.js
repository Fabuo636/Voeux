const form = document.getElementById('cardForm');
const resetBtn = document.getElementById('resetBtn');
const suggestionsList = document.getElementById('suggestionsList');
const statusPill = document.getElementById('statusPill');

const previewMessage = document.getElementById('previewMessage');
const previewOccasion = document.getElementById('previewOccasion');
const previewSign = document.getElementById('previewSign');
const previewCanvas = document.getElementById('previewCanvas');

const chooseBtn = document.getElementById('chooseBtn');
const copyBtn = document.getElementById('copyBtn');

const howBtn = document.getElementById('howBtn');
const howModal = document.getElementById('howModal');
const howClose = document.getElementById('howClose');

const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');

const backToFormBtn = document.getElementById('backToFormBtn');
const goPreviewBtn = document.getElementById('goPreviewBtn');
const backToSuggestionsBtn = document.getElementById('backToSuggestionsBtn');

const relationshipSelect = document.getElementById('relationship');
const messageTypeSelect = document.getElementById('messageType');

const formHeader = document.querySelector('#cardForm .card-header');

let selectedText = '';
let selectedBodyText = '';

const AI_WEBHOOK_URL = 'https://n8n.srv773455.hstgr.cloud/webhook/b57aa24b-a731-41af-bcb4-bd588e46d1c2';

const LOCAL_API_BASE = '../API';

const MIN_MESSAGE_CHARS = 90;
const TARGET_MESSAGE_CHARS = 140;
const MAX_MESSAGE_CHARS = 180;

const FIXED_OCCASION = 'Bonne année';

let currentFlow = {
  userId: null,
  recipientId: null,
  requestId: null,
  suggestions: [],
};

async function loadSelectOptions({ selectEl, path, labelKey = 'label_fr', valueKey = 'code' }) {
  if (!selectEl) return;
  const placeholder = selectEl.querySelector('option[value=""]');
  selectEl.innerHTML = '';
  if (placeholder) {
    selectEl.appendChild(placeholder);
  } else {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = 'Choisir…';
    selectEl.appendChild(opt);
  }

  try {
    const res = await apiJson(path);
    const items = res && res.ok === true && Array.isArray(res.data) ? res.data : [];

    items.forEach((it) => {
      const v = it && typeof it[valueKey] === 'string' ? it[valueKey] : '';
      const lbl = it && typeof it[labelKey] === 'string' ? it[labelKey] : v;
      if (!v) return;

      const opt = document.createElement('option');
      opt.value = v;
      opt.textContent = lbl;
      selectEl.appendChild(opt);
    });
  } catch (e) {
    // fallback: keep only placeholder
  }
}

function showStep(stepNum) {
  const map = {
    1: step1,
    2: step2,
    3: step3,
  };
  Object.values(map).forEach(el => el && el.classList.remove('is-active'));
  const target = map[stepNum];
  if (target) target.classList.add('is-active');

  if (formHeader) {
    formHeader.style.display = stepNum === 1 ? '' : 'none';
  }
}

function resetStepUi() {
  goPreviewBtn && (goPreviewBtn.disabled = true);
  chooseBtn && (chooseBtn.disabled = true);
}

function openHowModal() {
  if (!howModal) return;
  howModal.classList.add('is-open');
  howModal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function closeHowModal() {
  if (!howModal) return;
  howModal.classList.remove('is-open');
  howModal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

if (howBtn && howModal) {
  howBtn.addEventListener('click', openHowModal);
}

if (howClose && howModal) {
  howClose.addEventListener('click', closeHowModal);
}

if (howModal) {
  howModal.addEventListener('click', (e) => {
    const t = e.target;
    if (t && t.dataset && t.dataset.close === 'true') {
      closeHowModal();
    }
  });
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && howModal && howModal.classList.contains('is-open')) {
    closeHowModal();
  }
});

let lastFieldPointerDown = null;
let lastFieldPointerDownAt = 0;

function isFormField(el) {
  return !!(el && el.matches && el.matches('input, textarea, select'));
}

function installFocusGuard() {
  const capture = true;
  const WINDOW_MS = 800;

  const remember = (e) => {
    const t = e.target;
    if (!isFormField(t)) return;
    if (t.disabled) return;
    lastFieldPointerDown = t;
    lastFieldPointerDownAt = Date.now();
  };

  const shield = (e) => {
    const t = e.target;
    if (!isFormField(t)) return;
    remember(e);
    if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
    e.stopPropagation();
  };

  document.addEventListener('pointerdown', shield, capture);
  document.addEventListener('mousedown', shield, capture);
  document.addEventListener('click', shield, capture);

  document.addEventListener('focusout', (e) => {
    if (!lastFieldPointerDown) return;
    if (e.target !== lastFieldPointerDown) return;
    if (Date.now() - lastFieldPointerDownAt > WINDOW_MS) return;

    setTimeout(() => {
      const a = document.activeElement;
      if (a && isFormField(a)) return;
      if (!document.contains(lastFieldPointerDown)) return;
      lastFieldPointerDown.focus({ preventScroll: true });
    }, 0);
  }, capture);

  window.addEventListener('blur', () => {
    if (!lastFieldPointerDown) return;
    if (Date.now() - lastFieldPointerDownAt > WINDOW_MS) return;
    setTimeout(() => {
      if (!document.contains(lastFieldPointerDown)) return;
      lastFieldPointerDown.focus({ preventScroll: true });
    }, 0);
  }, capture);
}

installFocusGuard();

function setStatus(text) {
  if (!statusPill) return;
  statusPill.textContent = text;
}

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function getCardBackgroundAsset({ recipientGender, background }) {
  const bg = background === 'red' ? 'red' : 'black';
  const gender = recipientGender === 'female' ? 'female' : 'male';

  if (gender === 'female' && bg === 'black') return '../medias/Visuel-Voeux-Noel-Fille-Sans-texte-Fond-noir.png';
  if (gender === 'female' && bg === 'red') return '../medias/Visuel-Voeux-Noel-Fille-Sans-texte-Fond-rouge.png';
  if (gender === 'male' && bg === 'black') return '../medias/Visuel-Voeux-Noel-Garçon-Sans-texte-Fond-noir.png';
  return '../medias/Visuel-Voeux-Noel-Garçon-Sans-texte-Fond-rouge.png';
}

function applyCardBackgroundFromForm() {
  if (!previewCanvas) return;
  const recipientGender = document.getElementById('recipientGender')?.value.trim() || '';
  const background = document.getElementById('cardBackground')?.value.trim() || 'black';
  const genderOk = recipientGender === 'male' || recipientGender === 'female';

  if (!genderOk) {
    previewCanvas.style.backgroundImage = '';
    return;
  }

  const asset = getCardBackgroundAsset({ recipientGender, background });
  previewCanvas.style.backgroundImage = `url('${asset}')`;
  previewCanvas.style.backgroundSize = 'cover';
  previewCanvas.style.backgroundPosition = 'center';
  previewCanvas.style.backgroundRepeat = 'no-repeat';
}

document.getElementById('recipientGender')?.addEventListener('change', applyCardBackgroundFromForm);
document.getElementById('cardBackground')?.addEventListener('change', applyCardBackgroundFromForm);
applyCardBackgroundFromForm();

function renderSuggestions(items) {
  suggestionsList.innerHTML = '';

  if (!items.length) {
    suggestionsList.innerHTML = '<div class="empty-state">Aucune proposition.</div>';
    return;
  }

  items.forEach((it, idx) => {
    const el = document.createElement('div');
    el.className = 'suggestion';
    el.dataset.text = it.text;

    el.innerHTML = `
      <div class="suggestion-title">
        <div class="suggestion-label">Proposition ${idx + 1}</div>
        <div class="suggestion-meta">Cliquer pour sélectionner</div>
      </div>
      <div class="suggestion-text">${escapeHtml(it.text)}</div>
    `;

    el.addEventListener('click', () => {
      document.querySelectorAll('.suggestion').forEach(s => s.classList.remove('selected'));
      el.classList.add('selected');
      const senderName = document.getElementById('senderName').value.trim();
      selectedBodyText = stripGreetingAndSignature(it.text);
      selectedText = buildFinalText({ body: selectedBodyText, senderName });

      const recipientName = document.getElementById('recipientName').value.trim();
      previewOccasion.textContent = FIXED_OCCASION;
      const greetEl = document.getElementById('previewGreeting');
      if (greetEl) {
        greetEl.textContent = recipientName ? `Cher ${recipientName},` : 'Cher…';
      }
      previewMessage.textContent = selectedBodyText || '…';
      previewSign.textContent = senderName ? `_ ${senderName}` : '—';
      chooseBtn.disabled = false;
      if (goPreviewBtn) goPreviewBtn.disabled = false;

      setStatus('Sélectionné');
    });

    suggestionsList.appendChild(el);
  });
}

function generateLocalSuggestions({ senderName, recipientName, relationship, occasion, tone }) {
  const t = tone ? tone : 'chaleureux';

  const longTail = ` Je te souhaite de la joie, de la sérénité et de belles réussites pour la suite.`;

  const base = [
    `Cher/Chère ${recipientName}, en ce ${occasion}, je t’adresse des vœux ${t}. Merci d’être un(e) ${relationship} précieux(se) dans ma vie. ${longTail} — ${senderName}`,
    `${recipientName}, pour ce ${occasion}, reçois mes vœux ${t} et sincères. Que cette période t’apporte de belles opportunités et de bons moments. — ${senderName}`,
    `À ${recipientName} : je te souhaite un ${occasion} lumineux et apaisant. Que tout ce qui compte pour toi avance dans le bon sens. — ${senderName}`,
    `${recipientName}, je pense à toi en ce ${occasion}. Que cette nouvelle étape t’apporte énergie, confiance et réussite. — ${senderName}`,
  ];

  return base
    .map(s => s.replaceAll('  ', ' ').trim())
    .map(text => {
      if (text.length >= MIN_MESSAGE_CHARS) return text;
      return (text + ' ' + longTail).trim();
    })
    .slice(0, 4)
    .map(text => ({ text }));
}

async function apiJson(path, { method = 'GET', body } = {}) {
  const res = await fetch(`${LOCAL_API_BASE}/${path}`, {
    method,
    headers: {
      'Content-Type': 'application/json',
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  const ct = res.headers.get('content-type') || '';
  const data = ct.includes('application/json') ? await res.json() : await res.text();

  if (!res.ok) {
    const msg = typeof data === 'string' ? data : (data && data.error ? String(data.error) : 'Erreur API');
    throw new Error(msg);
  }

  return data;
}

// Chargement dynamique des listes depuis l'API
loadSelectOptions({ selectEl: relationshipSelect, path: 'relationships.php' });
loadSelectOptions({ selectEl: messageTypeSelect, path: 'message_types.php' });

function buildPublicVoeuxLink(publicCode) {
  const u = new URL('voeux.html', window.location.href);
  u.searchParams.set('code', String(publicCode || '').trim());
  return u.toString();
}

async function sendSmsToRecipient({ recipientPhone, smsText }) {
  return apiJson('sms_send.php', {
    method: 'POST',
    body: {
      senderid: 'MUTZIG',
      mobiles: recipientPhone,
      sms: smsText,
      scheduletime: '',
    },
  });
}

async function ensureUser({ fullName, phone }) {
  const payload = {
    full_name: fullName,
    phone: phone || null,
  };
  const res = await apiJson('users.php', { method: 'POST', body: payload });
  if (!res || res.ok !== true || typeof res.id !== 'number') {
    throw new Error('Création user échouée');
  }
  return res.id;
}

async function createRecipient({ ownerUserId, fullName, phone, gender }) {
  const payload = {
    owner_user_id: ownerUserId,
    full_name: fullName,
    phone: phone || null,
    gender: gender || 'unknown',
  };
  const res = await apiJson('recipients.php', { method: 'POST', body: payload });
  if (!res || res.ok !== true || typeof res.id !== 'number') {
    throw new Error('Création recipient échouée');
  }
  return res.id;
}

async function createMessageRequest({ userId, recipientId, relationship, occasion, messageType, tone }) {
  const payload = {
    user_id: userId,
    recipient_id: recipientId,
    output_lang: 'fr',
    status: 'draft',
    tone: tone || null,
    constraints: {
      relationship,
      occasion,
      message_type: messageType,
    },
  };

  const res = await apiJson('message_requests.php', { method: 'POST', body: payload });
  if (!res || res.ok !== true || typeof res.id !== 'number') {
    throw new Error('Création message_request échouée');
  }
  return res.id;
}

async function saveCandidates({ requestId, items }) {
  const payload = {
    request_id: requestId,
    items: items.map((it, i) => ({
      variant_index: i + 1,
      content_fr: it.text,
      content_en: null,
      model: 'n8n',
    })),
  };

  const res = await apiJson('message_candidates.php', { method: 'POST', body: payload });
  if (!res || res.ok !== true) {
    throw new Error('Sauvegarde des propositions échouée');
  }

  await apiJson(`message_requests.php?id=${encodeURIComponent(requestId)}`, {
    method: 'PUT',
    body: { status: 'generated' },
  });
}

async function saveChosenMessage({ requestId, finalText, background }) {
  const payload = {
    request_id: requestId,
    final_content_fr: finalText,
    final_content_en: null,
    background: background || null,
  };

  const res = await apiJson('messages.php', { method: 'POST', body: payload });
  if (!res || res.ok !== true) {
    throw new Error('Sauvegarde du message final échouée');
  }
  return res;
}

function buildAiPayload({ senderName, senderPhone, recipientName, recipientPhone, recipientGender, relationship, occasion, messageType, tone }) {
  const now = new Date();
  const userId = 42;

  return {
    question: 'generate_wishes',
    sessionId: `session_${userId}_${now.getTime()}`,
    sender_name: senderName,
    sender_phone: senderPhone || null,
    recipient_name: recipientName,
    recipient_phone: recipientPhone || null,
    recipient_gender: recipientGender || 'unknown',
    relationship,
    occasion,
    message_type: messageType,
    tone: tone || 'warm',
    lang: 'fr',
    count: 5,
    min_chars: MIN_MESSAGE_CHARS,
    target_chars: TARGET_MESSAGE_CHARS,
    max_chars: MAX_MESSAGE_CHARS,
    context: {
      application: 'Cartes de voeux',
      assistant: 'Zena',
      type: 'chat',
    },
    userInfos: {
      userId,
      role: 'user',
      timestamp: now.toISOString(),
    },
    wish: {
      sender_name: senderName,
      sender_phone: senderPhone || null,
      recipient_name: recipientName,
      recipient_phone: recipientPhone || null,
      recipient_gender: recipientGender || 'unknown',
      relationship,
      occasion,
      message_type: messageType,
      tone: tone || 'warm',
      lang: 'fr',
      count: 5,
      min_chars: MIN_MESSAGE_CHARS,
      target_chars: TARGET_MESSAGE_CHARS,
      max_chars: MAX_MESSAGE_CHARS,
    },
  };
}

function normalizeText(s) {
  return String(s || '').replace(/\s+/g, ' ').trim();
}

function clampTextLength(text) {
  const t = normalizeText(text);
  if (t.length <= MAX_MESSAGE_CHARS) return t;

  const head = t.slice(0, MAX_MESSAGE_CHARS);
  const punct = Math.max(
    head.lastIndexOf('.'),
    head.lastIndexOf('!'),
    head.lastIndexOf('?'),
    head.lastIndexOf(';')
  );

  const cut = punct >= MIN_MESSAGE_CHARS ? punct + 1 : MAX_MESSAGE_CHARS;
  return head.slice(0, cut).trim();
}

function stripGreetingAndSignature(text) {
  let t = normalizeText(text);

  t = t.replace(/^(cher|ch[eè]re|bonjour|bonsoir)\s+[^,!?]{1,40}[,!?]\s*/i, '');
  t = t.replace(/\s*(—|-)\s*[^\n]{1,40}\s*$/i, '');
  t = t.replace(/\s*_[\s\S]{0,40}$/i, '');

  return normalizeText(t);
}

function buildFinalText({ body, senderName }) {
  const sign = senderName ? `_ ${senderName}` : '';
  const b = normalizeText(body);
  if (!sign) return b;
  return `${b}\n${sign}`.trim();
}

function enforceMinChars(items) {
  return (items || [])
    .map(it => ({ text: clampTextLength(it.text) }))
    .filter(it => it.text.length >= MIN_MESSAGE_CHARS);
}

function extractAiMessages(payload) {
  if (payload == null) return [];

  if (Array.isArray(payload)) {
    return payload
      .map(v => (typeof v === 'string' ? v : (v && typeof v.text === 'string' ? v.text : null)))
      .filter(Boolean);
  }

  if (typeof payload === 'string') {
    return payload
      .split('\n')
      .map(s => s.trim())
      .filter(Boolean);
  }

  if (payload && typeof payload === 'object') {
    const candidates = [];
    if (Array.isArray(payload.messages)) candidates.push(...payload.messages);
    if (Array.isArray(payload.data)) candidates.push(...payload.data);
    if (Array.isArray(payload.choices)) {
      candidates.push(
        ...payload.choices.map(c => {
          if (!c) return null;
          if (typeof c === 'string') return c;
          if (c.message && typeof c.message.content === 'string') return c.message.content;
          if (typeof c.text === 'string') return c.text;
          return null;
        })
      );
    }

    if (candidates.length) {
      return candidates
        .map(v => (typeof v === 'string' ? v : (v && typeof v.text === 'string' ? v.text : null)))
        .filter(Boolean);
    }
  }

  return [];
}

async function generateAiSuggestions(input) {
  const payload = buildAiPayload(input);

  const res = await fetch(AI_WEBHOOK_URL, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const ct = res.headers.get('content-type') || '';
  let data;
  if (ct.includes('application/json')) {
    data = await res.json();
  } else {
    data = await res.text();
  }

  if (!res.ok) {
    const msg = typeof data === 'string' ? data : (data && data.error ? String(data.error) : 'Erreur API IA');
    throw new Error(msg);
  }

  const messages = extractAiMessages(data);
  return messages.map(text => ({ text }));
}

form.addEventListener('submit', (e) => {
  e.preventDefault();

  const senderName = document.getElementById('senderName').value.trim();
  const senderPhone = document.getElementById('senderPhone')?.value.trim() || '';
  const recipientName = document.getElementById('recipientName').value.trim();
  const recipientPhone = document.getElementById('recipientPhone')?.value.trim() || '';
  const recipientGender = document.getElementById('recipientGender')?.value.trim() || '';
  const relationship = document.getElementById('relationship').value.trim();
  const messageType = document.getElementById('messageType')?.value.trim() || '';
  const occasion = FIXED_OCCASION;
  const tone = document.getElementById('tone').value.trim();

  const genderOk = recipientGender === 'male' || recipientGender === 'female';

  if (!senderName || !recipientName || !genderOk || !relationship || !messageType) {
    setStatus('Champs manquants');
    return;
  }

  applyCardBackgroundFromForm();

  setStatus('Génération…');
  chooseBtn.disabled = true;
  if (goPreviewBtn) goPreviewBtn.disabled = true;
  selectedText = '';

  currentFlow = {
    userId: null,
    recipientId: null,
    requestId: null,
    suggestions: [],
  };

  (async () => {
    try {
      setStatus('Enregistrement…');

      const userId = await ensureUser({
        fullName: senderName,
        phone: senderPhone,
      });

      const recipientId = await createRecipient({
        ownerUserId: userId,
        fullName: recipientName,
        phone: recipientPhone,
        gender: recipientGender,
      });

      const requestId = await createMessageRequest({
        userId,
        recipientId,
        relationship,
        occasion,
        messageType,
        tone,
      });

      currentFlow.userId = userId;
      currentFlow.recipientId = recipientId;
      currentFlow.requestId = requestId;

      setStatus('Génération IA…');
      const items = await generateAiSuggestions({ senderName, senderPhone, recipientName, recipientPhone, recipientGender, relationship, occasion, messageType, tone });
      const filtered = enforceMinChars(items);
      if (!filtered.length) {
        throw new Error(`L’IA a retourné des messages trop courts (minimum ${MIN_MESSAGE_CHARS} caractères)`);
      }

      currentFlow.suggestions = filtered;

      setStatus('Sauvegarde propositions…');
      await saveCandidates({ requestId, items: filtered });

      renderSuggestions(filtered);
      setStatus('Propositions prêtes');

      showStep(2);
    } catch (err) {
      const msg = err && err.message ? err.message : 'Erreur IA';
      const fallback = generateLocalSuggestions({ senderName, recipientName, relationship, occasion, tone });
      const fbFiltered = enforceMinChars(fallback);
      renderSuggestions(fbFiltered);
      setStatus(`IA indisponible: ${msg}`);

      showStep(2);
    }
  })();
});

resetBtn.addEventListener('click', () => {
  form.reset();
  suggestionsList.innerHTML = '<div class="empty-state">Remplis le formulaire puis clique sur « Générer des propositions ».</div>';
  previewMessage.textContent = 'Choisis une proposition pour l’afficher ici.';
  previewOccasion.textContent = FIXED_OCCASION;
  previewSign.textContent = '—';
  applyCardBackgroundFromForm();
  resetStepUi();
  selectedText = '';
  selectedBodyText = '';
  currentFlow = {
    userId: null,
    recipientId: null,
    requestId: null,
    suggestions: [],
  };
  setStatus('Prêt');

  showStep(1);
});

backToFormBtn && backToFormBtn.addEventListener('click', () => {
  showStep(1);
});

backToSuggestionsBtn && backToSuggestionsBtn.addEventListener('click', () => {
  showStep(2);
});

goPreviewBtn && goPreviewBtn.addEventListener('click', () => {
  if (!selectedText) return;
  showStep(3);
});

copyBtn.addEventListener('click', async () => {
  if (!selectedText) return;

  try {
    await navigator.clipboard.writeText(selectedText);
    setStatus('Copié');
    setTimeout(() => setStatus('Sélectionné'), 900);
  } catch {
    setStatus('Impossible de copier');
  }
});

chooseBtn.addEventListener('click', () => {
  if (!selectedText) return;
  showStep(3);
  (async () => {
    try {
      if (!currentFlow.requestId) {
        setStatus('Validé');
        return;
      }

      const recipientGender = document.getElementById('recipientGender')?.value.trim() || '';
      const background = document.getElementById('cardBackground')?.value.trim() || 'black';
      const bgAsset = getCardBackgroundAsset({ recipientGender, background });

      setStatus('Enregistrement du choix…');
      const payload = {
        requestId: currentFlow.requestId,
        finalText: selectedText,
        background: bgAsset,
      };
      const res = await saveChosenMessage(payload);

      const publicCode = res && res.public_code ? String(res.public_code) : '';
      setStatus('Vous avez bien envoyé les vœux');

      const recipientPhone = document.getElementById('recipientPhone')?.value.trim() || '';
      if (publicCode && recipientPhone) {
        const link = (res && typeof res.public_url === 'string' && res.public_url.trim() !== '')
          ? res.public_url.trim()
          : buildPublicVoeuxLink(publicCode);
        const recipientName = document.getElementById('recipientName')?.value.trim() || '';
        const senderName = document.getElementById('senderName')?.value.trim() || '';
        const toName = recipientName || 'cher client';
        const fromName = senderName || 'Mützig';
        const smsText = `Hello ${toName}, ${fromName} vous envoie ses voeux pour une bonne et audacieuse année 2026. e-carte ici ${link}`;
        try {
          await sendSmsToRecipient({ recipientPhone, smsText });
        } catch (smsErr) {
        }
      }

      setTimeout(() => {
        resetBtn && resetBtn.click();
      }, 1600);
    } catch (err) {
      const msg = err && err.message ? err.message : 'Erreur';
      setStatus(`Erreur sauvegarde: ${msg}`);
    }
  })();
});
