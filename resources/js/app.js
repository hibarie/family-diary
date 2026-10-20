const TOKEN_KEY = 'childcare_token';

const state = {
    token: localStorage.getItem(TOKEN_KEY),
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
    selectedDate: formatDate(new Date()),
    calendarDays: {},
    children: [],
    people: [],
};

function describeAge(birthDate) {
    if (!birthDate) return '';
    const [by, bm, bd] = String(birthDate).slice(0, 10).split('-').map(Number);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const birth = new Date(by, bm - 1, bd);

    if (isNaN(birth) || birth > today) return '';

    let months = (today.getFullYear() - by) * 12 + (today.getMonth() - (bm - 1));
    if (today.getDate() < bd) months -= 1;

    const days = Math.round((today - birth) / 86400000) + 1;

    return `${Math.floor(months / 12)}歳${months % 12}ヶ月(生まれて${days}日目)`;
}

function formatDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function el(id) {
    return document.getElementById(id);
}

function showError(message) {
    const box = el('error-message');
    box.textContent = message;
    box.classList.remove('hidden');
}

function clearError() {
    el('error-message').classList.add('hidden');
    el('error-message').textContent = '';
}

async function api(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        ...(options.headers || {}),
    };

    if (options.body && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    if (state.token) {
        headers.Authorization = `Bearer ${state.token}`;
    }

    const response = await fetch(`/api${path}`, { ...options, headers });

    if (response.status === 204) {
        return null;
    }

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const message = (data && data.message) || `リクエストに失敗しました (${response.status})`;
        const error = new Error(message);
        error.status = response.status;
        error.data = data;
        throw error;
    }

    return data;
}

function setToken(token) {
    state.token = token;
    if (token) {
        localStorage.setItem(TOKEN_KEY, token);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

function showAuthView() {
    el('auth-section').classList.remove('hidden');
    el('app-section').classList.add('hidden');
    el('logout-button').classList.add('hidden');
}

function showAppView() {
    el('auth-section').classList.add('hidden');
    el('app-section').classList.remove('hidden');
    el('logout-button').classList.remove('hidden');
}

/* ---------- 認証 ---------- */

function setupAuthTabs() {
    document.querySelectorAll('.auth-tab').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.authTab;

            document.querySelectorAll('.auth-tab').forEach((t) => {
                const active = t === tab;
                t.classList.toggle('bg-orange-400', active);
                t.classList.toggle('text-white', active);
                t.classList.toggle('bg-orange-50', !active);
                t.classList.toggle('text-orange-500', !active);
            });

            el('login-form').classList.toggle('hidden', target !== 'login');
            el('register-form').classList.toggle('hidden', target !== 'register');
        });
    });
}

async function handleLogin(event) {
    event.preventDefault();
    clearError();

    const form = new FormData(event.target);

    try {
        const data = await api('/login', {
            method: 'POST',
            body: JSON.stringify({
                email: form.get('email'),
                password: form.get('password'),
            }),
        });

        setToken(data.token);
        await bootApp();
    } catch (error) {
        showError(error.message);
    }
}

async function handleRegister(event) {
    event.preventDefault();
    clearError();

    const form = new FormData(event.target);

    try {
        const data = await api('/register', {
            method: 'POST',
            body: JSON.stringify({
                name: form.get('name'),
                email: form.get('email'),
                password: form.get('password'),
                invite_code: form.get('invite_code') || null,
            }),
        });

        setToken(data.token);
        await bootApp();
    } catch (error) {
        showError(error.message);
    }
}

async function handleLogout() {
    try {
        await api('/logout', { method: 'POST' });
    } catch (error) {
        // ログアウト自体が失敗してもローカルのトークンは消す
    }

    setToken(null);
    showAuthView();
}

/* ---------- 家族 ---------- */

async function loadFamily() {
    const families = await api('/families');
    const family = families[0];

    if (!family) {
        return;
    }

    el('family-name-label').textContent = family.name;
    el('family-invite-code').textContent = family.invite_code;
}

/* ---------- 家族の情報(パパ・ママ・子ども) ---------- */

const ROLE_ORDER = ['パパ', 'ママ', 'おじいちゃん', 'おばあちゃん', 'その他', 'こども'];
const ADD_ROLES = ['パパ', 'ママ', 'こども', 'おじいちゃん', 'おばあちゃん', 'その他'];

// 並び順の番号(知らない続柄は「その他」と同じ位置)
const roleRank = (role) => {
    const i = ROLE_ORDER.indexOf(role);
    return i === -1 ? ROLE_ORDER.indexOf('その他') : i;
};

// 画面に出す1人分の情報をそろえる(パパ・ママと子どもで共通の形にする)
// 続柄ごとの色(丸いアイコンと続柄ラベル)
const ROLE_COLORS = {
    'パパ': { bg: '#FFD9A8', fg: '#C77A1E' },
    'ママ': { bg: '#FFC9D6', fg: '#C4506F' },
    'こども': { bg: '#BFEBCF', fg: '#2F9A6C' },
};
const roleColor = (role) => ROLE_COLORS[role] || { bg: '#EADFCB', fg: '#8A6F52' };

function toPerson(raw, kind) {
    return {
        kind,
        id: raw.id,
        name: raw.name,
        role: kind === 'child' ? 'こども' : raw.role,
        birth_date: raw.birth_date,
        avatar_url: raw.avatar_url,
        base: kind === 'child' ? '/children' : '/family-members',
    };
}

async function loadChildren() {
    const [children, members] = await Promise.all([api('/children'), api('/family-members')]);

    state.children = children;

    const people = [
        ...members.map((m) => toPerson(m, 'member')),
        ...children.map((c) => toPerson(c, 'child')),
    ].sort((a, b) => roleRank(a.role) - roleRank(b.role));

    state.people = people;
    renderEventPersonTabs();

    const list = el('children-list');
    list.innerHTML = '';
    el('children-empty').classList.toggle('hidden', people.length > 0);

    people.forEach((person) => {
        const li = document.createElement('li');
        li.className = `flex items-center gap-3 fd-person${person.kind === 'child' ? ' fd-person--child' : ''}`;

        const initial = escapeHtml((person.name || '?').slice(0, 1));
        const avatar = person.avatar_url
            ? `<img src="${escapeHtml(person.avatar_url)}" alt="" class="absolute inset-0 w-full h-full object-cover">`
            : `<span class="font-bold" style="color:${roleColor(person.role).fg}">${initial}</span>`;

        li.innerHTML = `
            <label class="relative w-12 h-12 shrink-0 rounded-full flex items-center justify-center overflow-hidden cursor-pointer" style="background:${roleColor(person.role).bg}" title="写真を変更">
                ${avatar}
                <input type="file" accept="image/*" class="hidden">
            </label>
            <div class="flex-1 min-w-0">
                <div class="font-medium text-stone-700">
                    <span class="text-xs rounded-full px-2 py-0.5 mr-1" style="background:${roleColor(person.role).bg}">${escapeHtml(person.role)}</span>${escapeHtml(person.name)}
                </div>
                <div class="text-xs text-stone-500">${escapeHtml(describeAge(person.birth_date))}</div>
                <div class="text-xs text-stone-400">${person.birth_date ? escapeHtml(String(person.birth_date).slice(0, 10)) + ' 生まれ' : ''}</div>
            </div>
            <button type="button" data-edit class="text-xs text-orange-500 hover:text-orange-600 px-2 py-1 rounded-md hover:bg-orange-100">編集</button>
        `;

        li.querySelector('[data-edit]').addEventListener('click', () => startEditPerson(li, person));
        li.querySelector('input[type="file"]').addEventListener('change', (event) => {
            handleChangePersonPhoto(person, event.target.files[0]);
        });

        list.appendChild(li);
    });

    // 家族はいつでも追加できる(続柄を選んで登録する)
    const select = el('child-form').querySelector('select[name="role"]');
    const currentRole = select.value;
    select.innerHTML = ADD_ROLES.map((r) => `<option value="${r}">${r}</option>`).join('');
    if (ADD_ROLES.includes(currentRole)) select.value = currentRole;

    el('toggle-child-form').classList.remove('hidden');
}

function startEditPerson(li, person) {
    const inputClass = 'w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 bg-white';

    li.className = `fd-person${person.kind === 'child' ? ' fd-person--child' : ''}`;
    li.innerHTML = `
        <form class="space-y-2">
            <div>
                <label class="block text-xs text-stone-500 mb-1">名前(${escapeHtml(person.role)})</label>
                <input type="text" name="name" required autocomplete="off" value="${escapeHtml(person.name)}" class="${inputClass}">
            </div>
            <div>
                <label class="block text-xs text-stone-500 mb-1">生年月日</label>
                <input type="date" name="birth_date" required value="${escapeHtml(String(person.birth_date || '').slice(0, 10))}" max="${formatDate(new Date())}" class="${inputClass}">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-md bg-orange-400 text-white text-sm font-medium hover:bg-orange-500">保存する</button>
                <button type="button" data-cancel class="flex-1 py-2 rounded-md border border-stone-200 bg-white text-stone-500 text-sm hover:bg-stone-50">キャンセル</button>
            </div>
            <button type="button" data-delete class="w-full text-xs text-stone-400 hover:text-red-500 py-1">この人を削除する</button>
        </form>
    `;

    li.querySelector('[data-cancel]').addEventListener('click', () => loadChildren().catch((error) => showError(error.message)));

    li.querySelector('[data-delete]').addEventListener('click', async () => {
        if (!confirm(`${person.name}さんを削除しますか？`)) return;
        clearError();
        try {
            await api(`${person.base}/${person.id}`, { method: 'DELETE' });
            await Promise.all([loadChildren(), selectDay(state.selectedDate)]);
        } catch (error) {
            showError(error.message);
        }
    });

    li.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        const form = new FormData(event.target);

        try {
            await api(`${person.base}/${person.id}`, {
                method: 'PUT',
                body: JSON.stringify({
                    name: form.get('name'),
                    birth_date: form.get('birth_date'),
                }),
            });

            await Promise.all([loadChildren(), selectDay(state.selectedDate)]);
        } catch (error) {
            showError(error.message);
        }
    });
}

async function handleChangePersonPhoto(person, file) {
    if (!file) {
        return;
    }

    clearError();

    const body = new FormData();
    body.append('photo', file);

    try {
        await api(`${person.base}/${person.id}/photo`, { method: 'POST', body });
        await loadChildren();
    } catch (error) {
        showError(error.message);
    }
}

function handleChildPhotoPreview(event) {
    const file = event.target.files[0];
    const preview = el('child-photo-preview');
    const placeholder = el('child-photo-placeholder');

    if (!file) {
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        return;
    }

    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
    placeholder.classList.add('hidden');
}

// 予定フォームの「だれの予定」タブ(パパ・ママ・こども)
function renderEventPersonTabs() {
    const box = el('event-person-tabs');
    const input = el('event-person');
    const keyOf = (p) => `${p.kind}:${p.id}`;

    const keys = state.people.map(keyOf);
    if (!keys.includes(input.value)) {
        input.value = keys[0] || '';
    }

    box.innerHTML = '';

    state.people.forEach((person) => {
        const selected = keyOf(person) === input.value;
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = person.name;
        button.className = selected
            ? 'px-3 py-1.5 rounded-full text-sm bg-sky-500 text-white'
            : 'px-3 py-1.5 rounded-full text-sm bg-white border border-stone-200 text-stone-600 hover:bg-sky-50';
        button.addEventListener('click', () => {
            input.value = keyOf(person);
            renderEventPersonTabs();
        });
        box.appendChild(button);
    });
}

async function handleAddChild(event) {
    event.preventDefault();
    clearError();

    const form = new FormData(event.target);
    const role = form.get('role');

    // 写真を選んでいないときは、空のファイル欄を送らない
    const photo = form.get('photo');
    if (!photo || photo.size === 0) {
        form.delete('photo');
    }

    const isChild = role === 'こども';
    if (isChild) form.delete('role');

    try {
        await api(isChild ? '/children' : '/family-members', {
            method: 'POST',
            body: form,
        });

        event.target.reset();
        handleChildPhotoPreview({ target: { files: [] } });
        el('child-form').classList.add('hidden');
        await loadChildren();
    } catch (error) {
        showError(error.message);
    }
}

/* ---------- カレンダー ---------- */

async function loadCalendar() {
    const data = await api(`/calendar/${state.year}/${state.month}`);
    state.calendarDays = {};

    data.days.forEach((day) => {
        state.calendarDays[day.date] = day;
    });

    renderCalendar();
}

function renderCalendar() {
    el('calendar-title').textContent = `${state.year}年 ${state.month}月`;

    const grid = el('calendar-grid');
    grid.innerHTML = '';

    ['日', '月', '火', '水', '木', '金', '土'].forEach((label) => {
        const cell = document.createElement('div');
        cell.className = 'text-stone-400 font-medium py-1';
        cell.textContent = label;
        grid.appendChild(cell);
    });

    const firstDay = new Date(state.year, state.month - 1, 1);
    const daysInMonth = new Date(state.year, state.month, 0).getDate();
    const startWeekday = firstDay.getDay();

    for (let i = 0; i < startWeekday; i += 1) {
        grid.appendChild(document.createElement('div'));
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const dateStr = `${state.year}-${String(state.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const info = state.calendarDays[dateStr];
        const isSelected = dateStr === state.selectedDate;
        const isToday = dateStr === formatDate(new Date());

        const cell = document.createElement('button');
        cell.type = 'button';
        cell.className = [
            'fd-day py-2 flex flex-col items-center gap-0.5 hover:bg-orange-50',
            isSelected ? 'bg-orange-400 text-white hover:bg-orange-400' : 'text-stone-600',
            isToday && !isSelected ? 'ring-1 ring-orange-300' : '',
        ].join(' ');

        const hasRecords = info && (info.diary_count > 0 || info.school_event_count > 0);

        cell.innerHTML = `
            <span>${day}</span>
            <span class="w-1.5 h-1.5 rounded-full ${hasRecords ? (isSelected ? 'bg-white' : 'bg-orange-400') : 'bg-transparent'}"></span>
        `;

        cell.addEventListener('click', () => selectDay(dateStr));
        grid.appendChild(cell);
    }
}

async function changeMonth(offset) {
    let newMonth = state.month + offset;
    let newYear = state.year;

    if (newMonth > 12) {
        newMonth = 1;
        newYear += 1;
    } else if (newMonth < 1) {
        newMonth = 12;
        newYear -= 1;
    }

    state.month = newMonth;
    state.year = newYear;

    try {
        await loadCalendar();
    } catch (error) {
        showError(error.message);
    }
}

/* ---------- 日ごとの記録 ---------- */

async function selectDay(dateStr) {
    state.selectedDate = dateStr;
    renderCalendar();

    try {
        const data = await api(`/calendar/day/${dateStr}`);
        renderDay(data);
    } catch (error) {
        showError(error.message);
    }
}

function renderDay(data) {
    const [year, month, day] = data.date.split('-');
    el('day-title').textContent = `${year}年${month}月${day}日の記録`;

    const diaryBox = el('diary-entries');
    const eventBox = el('school-events');
    diaryBox.innerHTML = '';
    eventBox.innerHTML = '';

    // 日記は「+ 日記を書く」か各日記の「編集」を押したときだけ入力欄が開く
    data.diary_entries.forEach((entry) => diaryBox.appendChild(buildDiaryView(entry)));

    const typeLabels = EVENT_TYPE_LABELS;

    // まさたか → りえ → ひばり の順に並べる
    const ownerIndex = (item) => state.people.findIndex((p) => (
        item.family_member_id
            ? p.kind === 'member' && p.id === item.family_member_id
            : p.kind === 'child' && p.id === item.child_id
    ));
    const sortedEvents = [...data.school_events].sort((a, b) => ownerIndex(a) - ownerIndex(b));

    sortedEvents.forEach((eventItem) => {
        const owner = state.people.find((p) => (
            eventItem.family_member_id
                ? p.kind === 'member' && p.id === eventItem.family_member_id
                : p.kind === 'child' && p.id === eventItem.child_id
        ));
        const typeLabel = typeLabels[eventItem.event_type] || '';

        const card = document.createElement('div');
        card.className = 'fd-event';
        card.innerHTML = `
            <p class="font-medium text-stone-700">${owner ? escapeHtml(owner.name) : ''}${owner && typeLabel ? '　' : ''}${escapeHtml(typeLabel)}</p>
            ${eventItem.title ? `<p class="text-sm text-stone-600 mt-1">${escapeHtml(eventItem.title)}</p>` : ''}
            ${eventItem.memo ? `<p class="text-sm text-stone-600 mt-1 whitespace-pre-wrap">${escapeHtml(eventItem.memo)}</p>` : ''}
            <button type="button" data-edit class="mt-2 text-xs text-sky-500 hover:text-sky-600">編集</button>
        `;
        card.querySelector('[data-edit]').addEventListener('click', () => startEditEvent(card, eventItem, owner));
        eventBox.appendChild(card);
    });

    const isEmpty = data.school_events.length === 0;
    el('day-empty').classList.toggle('hidden', !isEmpty);
}

const EVENT_TYPE_LABELS = {
    event: '行事',
    belongings: '持ち物',
    meeting: '面談',
    kindergarten: '幼稚園',
    kindergarten_flat: '幼稚園・フラット',
    flat: 'フラット',
    work: '仕事',
    off: '非番',
    holiday: '休日',
    lesson: '習い事',
    care: '預かり',
    other: 'その他',
};

// 予定カードをその場で編集できる形にする(種類・予定・メモの変更と削除)
function startEditEvent(card, eventItem, owner) {
    const inputClass = 'w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-300 bg-white';

    // 「持ち物」は新しく選べないが、すでに持ち物の予定のときだけ選択肢に残す
    const types = Object.entries(EVENT_TYPE_LABELS).filter(
        ([value]) => value !== 'belongings' || eventItem.event_type === 'belongings'
    );
    const options = types.map(([value, label]) => (
        `<option value="${value}"${value === eventItem.event_type ? ' selected' : ''}>${label}</option>`
    )).join('');

    card.innerHTML = `
        <form class="space-y-2">
            <p class="text-xs text-stone-500">${owner ? escapeHtml(owner.name) : ''}の予定</p>
            <div>
                <label class="block text-xs text-stone-500 mb-1">種類</label>
                <select name="event_type" class="${inputClass}">${options}</select>
            </div>
            <div>
                <label class="block text-xs text-stone-500 mb-1">予定(任意)</label>
                <input type="text" name="title" autocomplete="off" value="${escapeHtml(eventItem.title || '')}" class="${inputClass}">
            </div>
            <div>
                <label class="block text-xs text-stone-500 mb-1">メモ(任意)</label>
                <textarea name="memo" rows="2" class="${inputClass}">${escapeHtml(eventItem.memo || '')}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 rounded-md bg-sky-500 text-white text-sm font-medium hover:bg-sky-600">保存する</button>
                <button type="button" data-cancel class="flex-1 py-2 rounded-md border border-stone-200 bg-white text-stone-500 text-sm hover:bg-stone-50">キャンセル</button>
            </div>
            <button type="button" data-delete class="w-full text-xs text-stone-400 hover:text-red-500 py-1">この予定を削除する</button>
        </form>
    `;

    const refresh = () => Promise.all([loadCalendar(), selectDay(state.selectedDate)]);

    card.querySelector('[data-cancel]').addEventListener('click', () => {
        selectDay(state.selectedDate).catch((error) => showError(error.message));
    });

    card.querySelector('[data-delete]').addEventListener('click', async () => {
        if (!confirm('この予定を削除しますか？')) return;
        clearError();
        try {
            await api(`/school-events/${eventItem.id}`, { method: 'DELETE' });
            await refresh();
        } catch (error) {
            showError(error.message);
        }
    });

    card.querySelector('form').addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();

        const form = new FormData(event.target);

        try {
            await api(`/school-events/${eventItem.id}`, {
                method: 'PUT',
                body: JSON.stringify({
                    event_type: form.get('event_type'),
                    title: form.get('title') || null,
                    memo: form.get('memo') || null,
                }),
            });
            await refresh();
        } catch (error) {
            showError(error.message);
        }
    });
}

// 画像・動画の公開パス(APP_URL に左右されないよう相対パスにする)
function mediaSrc(media) {
    return `/storage/${media.path}`;
}

function mediaThumb(media) {
    return media.type === 'video'
        ? `<video src="${escapeHtml(mediaSrc(media))}" controls class="w-full h-full object-cover"></video>`
        : `<img src="${escapeHtml(mediaSrc(media))}" alt="" class="w-full h-full object-cover">`;
}

// 日記の表示(文章と写真)。「編集」を押すと入力欄に切り替わる
function buildDiaryView(entry) {
    const card = document.createElement('div');
    card.className = 'fd-diary';

    const media = entry.media || [];
    card.innerHTML = `
        <p class="text-xs text-orange-500 mb-1">日記</p>
        <p class="text-sm text-stone-700 whitespace-pre-wrap">${escapeHtml(entry.body)}</p>
        ${media.length ? `<div class="grid grid-cols-3 gap-2 mt-2">${media.map((m) => `<div class="aspect-square rounded-md overflow-hidden bg-white">${mediaThumb(m)}</div>`).join('')}</div>` : ''}
        <button type="button" data-edit class="mt-2 text-xs text-orange-500 hover:text-orange-600">編集</button>
    `;

    card.startEdit = () => card.replaceWith(buildDiaryEditor(entry));
    card.querySelector('[data-edit]').addEventListener('click', card.startEdit);

    return card;
}

// 日記の入力欄。entry が null のときは新しく書く欄。写真も一緒に付けられる
function buildDiaryEditor(entry) {
    const box = document.createElement('div');
    box.className = 'fd-diary space-y-2';
    box.dataset.diaryEditor = '1';

    let media = [...((entry && entry.media) || [])];

    box.innerHTML = `
        <label class="block text-xs text-stone-500">日記</label>
        <textarea rows="4" placeholder="今日のできごと" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 bg-white">${escapeHtml(entry ? entry.body : '')}</textarea>
        <div data-media class="grid grid-cols-4 gap-2"></div>
        <label class="inline-flex items-center gap-1 text-sm text-orange-500 hover:text-orange-600 cursor-pointer">
            <span>📷 写真を付ける</span>
            <input type="file" accept="image/*" multiple class="hidden">
        </label>
        <p data-files class="text-xs text-stone-400"></p>
        <div class="flex gap-2">
            <button type="button" data-save class="flex-1 py-2 rounded-md bg-orange-400 text-white text-sm font-medium hover:bg-orange-500">保存する</button>
            <button type="button" data-cancel class="flex-1 py-2 rounded-md border border-stone-200 bg-white text-stone-500 text-sm hover:bg-stone-50">キャンセル</button>
        </div>
        ${entry ? '<button type="button" data-delete class="w-full text-xs text-stone-400 hover:text-red-500 py-1">この日記を削除する</button>' : ''}
    `;

    const textarea = box.querySelector('textarea');
    const fileInput = box.querySelector('input[type="file"]');
    const fileNote = box.querySelector('[data-files]');
    const mediaBox = box.querySelector('[data-media]');

    const renderMedia = () => {
        mediaBox.innerHTML = '';
        media.forEach((m) => {
            const cell = document.createElement('div');
            cell.className = 'relative aspect-square rounded-md overflow-hidden bg-white';
            cell.innerHTML = `${mediaThumb(m)}<button type="button" class="absolute bottom-1 inset-x-1 py-0.5 rounded bg-red-500/90 text-white text-xs hover:bg-red-600">削除</button>`;
            cell.querySelector('button').addEventListener('click', async () => {
                if (!confirm('この写真を削除しますか？')) return;
                clearError();
                try {
                    await api(`/media/${m.id}`, { method: 'DELETE' });
                    media = media.filter((x) => x.id !== m.id);
                    renderMedia();
                } catch (error) {
                    showError(error.message);
                }
            });
            mediaBox.appendChild(cell);
        });
    };
    renderMedia();

    fileInput.addEventListener('change', () => {
        fileNote.textContent = fileInput.files.length ? `${fileInput.files.length}枚 選択中(保存すると追加されます)` : '';
    });

    const finish = () => Promise.all([loadCalendar(), selectDay(state.selectedDate)]);

    box.querySelector('[data-cancel]').addEventListener('click', () => {
        selectDay(state.selectedDate).catch((error) => showError(error.message));
    });

    box.querySelector('[data-save]').addEventListener('click', async () => {
        clearError();

        const body = textarea.value.trim();
        if (!body) {
            showError('日記の文章を入力してください。');
            return;
        }

        try {
            let saved;
            if (entry) {
                saved = await api(`/diary-entries/${entry.id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ body }),
                });
            } else {
                saved = await api('/diary-entries', {
                    method: 'POST',
                    body: JSON.stringify({ body, entry_date: state.selectedDate }),
                });
            }

            for (const file of fileInput.files) {
                const form = new FormData();
                form.append('file', file);
                await api(`/diary-entries/${saved.id}/media`, { method: 'POST', body: form });
            }

            await finish();
        } catch (error) {
            showError(error.message);
            // 文章は保存済みでも写真だけ失敗することがあるので、最新の状態を出し直す
            await finish().catch(() => {});
        }
    });

    if (entry) {
        box.querySelector('[data-delete]').addEventListener('click', async () => {
            if (!confirm('この日の日記を削除しますか？')) return;
            clearError();
            try {
                await api(`/diary-entries/${entry.id}`, { method: 'DELETE' });
                await finish();
            } catch (error) {
                showError(error.message);
            }
        });
    }

    return box;
}

async function handleAddEvent(event) {
    event.preventDefault();
    clearError();

    const form = new FormData(event.target);

    try {
        const [kind, personId] = String(form.get('person')).split(':');
        const base = kind === 'member' ? '/family-members' : '/children';

        await api(`${base}/${personId}/school-events`, {
            method: 'POST',
            body: JSON.stringify({
                title: form.get('title'),
                event_type: form.get('event_type'),
                event_date: state.selectedDate,
                memo: form.get('memo') || null,
            }),
        });

        event.target.reset();
        el('event-form').classList.add('hidden');
        await Promise.all([loadCalendar(), selectDay(state.selectedDate)]);
    } catch (error) {
        showError(error.message);
    }
}

/* ---------- ユーティリティ ---------- */

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

/* ---------- 初期化 ---------- */

async function bootApp() {
    clearError();

    try {
        await api('/user');
    } catch (error) {
        setToken(null);
        showAuthView();
        return;
    }

    showAppView();

    try {
        await Promise.all([loadFamily(), loadChildren(), loadCalendar()]);
        await selectDay(state.selectedDate);
    } catch (error) {
        showError(error.message);
    }
}

function setupEventListeners() {
    setupAuthTabs();

    el('login-form').addEventListener('submit', handleLogin);
    el('register-form').addEventListener('submit', handleRegister);
    el('logout-button').addEventListener('click', handleLogout);

    el('toggle-child-form').addEventListener('click', () => {
        el('child-form').classList.toggle('hidden');
    });
    el('child-form').addEventListener('submit', handleAddChild);
    el('child-form').querySelector('input[name="birth_date"]').max = formatDate(new Date());
    el('child-form').querySelector('input[name="photo"]').addEventListener('change', handleChildPhotoPreview);


    el('toggle-diary-form').addEventListener('click', () => {
        const box = el('diary-entries');

        // すでに入力欄が開いていれば何もしない
        if (box.querySelector('[data-diary-editor]')) return;

        const first = box.firstElementChild;
        if (first && first.startEdit) {
            first.startEdit();
        } else {
            box.prepend(buildDiaryEditor(null));
        }
    });

    el('toggle-event-form').addEventListener('click', () => {
        if (state.people.length === 0) {
            showError('予定を書く前に、「家族の情報」で家族を登録してください。');
            return;
        }
        el('event-form').classList.toggle('hidden');
    });
    el('event-form').addEventListener('submit', handleAddEvent);

    el('prev-month').addEventListener('click', () => changeMonth(-1));
    el('next-month').addEventListener('click', () => changeMonth(1));
}

document.addEventListener('DOMContentLoaded', () => {
    setupEventListeners();

    if (state.token) {
        bootApp();
    } else {
        showAuthView();
    }
});
