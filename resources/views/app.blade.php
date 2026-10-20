<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Family Diary</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600&family=Zen+Maru+Gothic:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">

    <header class="fd-header">
        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center gap-3">
            <div class="w-14 h-14 shrink-0">
                <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="マスコットのねこ">
                    <circle cx="50" cy="50" r="48" fill="#DFF5E1"/>
                    <ellipse cx="50" cy="60" rx="30" ry="26" fill="#FFF3D6"/>
                    <path d="M26 44 L34 20 L44 40 Z" fill="#FFF3D6"/>
                    <path d="M74 44 L66 20 L56 40 Z" fill="#FFF3D6"/>
                    <path d="M29 42 L35 26 L41 39 Z" fill="#FFC9D6"/>
                    <path d="M71 42 L65 26 L59 39 Z" fill="#FFC9D6"/>
                    <circle cx="39" cy="58" r="4.2" fill="#5B4636"/>
                    <circle cx="61" cy="58" r="4.2" fill="#5B4636"/>
                    <ellipse cx="50" cy="66" rx="4" ry="3" fill="#FFB6A3"/>
                    <path d="M50 68 Q50 74 44 74" stroke="#5B4636" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M50 68 Q50 74 56 74" stroke="#5B4636" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <circle cx="30" cy="66" r="5" fill="#FFC9D6" opacity="0.7"/>
                    <circle cx="70" cy="66" r="5" fill="#FFC9D6" opacity="0.7"/>
                    <path d="M12 58 L28 55 M12 63 L28 61 M12 68 L28 66" stroke="#C9BBA8" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M88 58 L72 55 M88 63 L72 61 M88 68 L72 66" stroke="#C9BBA8" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-semibold tracking-wide leading-tight" style="font-family: 'Fredoka', ui-rounded, system-ui, sans-serif;"><span class="text-orange-400">Family</span> <span class="text-emerald-400">Diary</span> <span class="text-base align-middle">🐾</span></h1>
                <p id="family-name-label" class="text-xs text-stone-500"></p>
            </div>
            <button id="logout-button" type="button" class="hidden text-sm text-stone-500 hover:text-stone-700 px-3 py-1.5 rounded-md border border-stone-200 hover:bg-stone-50">
                ログアウト
            </button>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-6">

        <p id="error-message" class="hidden mb-4 rounded-md bg-red-50 text-red-700 text-sm px-4 py-3 border border-red-100"></p>

        {{-- ログイン / 登録 --}}
        <section id="auth-section" class="max-w-sm mx-auto">
            <div class="fd-card">
                <div class="flex gap-2 mb-5 text-sm">
                    <button type="button" data-auth-tab="login" class="auth-tab flex-1 py-2 rounded-md font-medium bg-orange-400 text-white">ログイン</button>
                    <button type="button" data-auth-tab="register" class="auth-tab flex-1 py-2 rounded-md font-medium bg-orange-50 text-orange-500">はじめて使う</button>
                </div>

                <form id="login-form" class="space-y-3">
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">メールアドレス</label>
                        <input type="email" name="email" required class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">パスワード</label>
                        <input type="password" name="password" required class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <button type="submit" class="w-full py-2 rounded-md bg-orange-400 text-white text-sm font-medium hover:bg-orange-500">
                        ログイン
                    </button>
                </form>

                <form id="register-form" class="space-y-3 hidden">
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">名前</label>
                        <input type="text" name="name" required class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">メールアドレス</label>
                        <input type="email" name="email" required class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">パスワード(8文字以上)</label>
                        <input type="password" name="password" required minlength="8" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">招待コード(すでに家族がいる場合のみ)</label>
                        <input type="text" name="invite_code" placeholder="任意" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <button type="submit" class="w-full py-2 rounded-md bg-orange-400 text-white text-sm font-medium hover:bg-orange-500">
                        はじめる
                    </button>
                </form>
            </div>
        </section>

        {{-- ログイン後のメイン画面 --}}
        <section id="app-section" class="hidden space-y-6">

            {{-- 家族情報 --}}
            <div class="fd-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-stone-700">家族の共有コード</h2>
                </div>
                <p class="text-sm text-stone-500 mt-1">このコードを家族に伝えると、同じ記録を一緒に見られます。</p>
                <p id="family-invite-code" class="mt-2 inline-block text-lg font-mono tracking-widest bg-orange-50 text-orange-600 px-3 py-1 rounded-md"></p>
            </div>

            {{-- 家族の情報 --}}
            <div class="fd-card">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-stone-700">家族の情報</h2>
                    <button type="button" id="toggle-child-form" class="fd-pill fd-pill-orange">+ 追加</button>
                </div>
                <ul id="children-list" class="space-y-2 text-sm"></ul>
                <p id="children-empty" class="text-sm text-stone-400">まだ登録されていません</p>

                <form id="child-form" class="hidden mt-4 space-y-3 border-t border-orange-100 pt-4">
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">続柄</label>
                        <select name="role" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-orange-300"></select>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="relative w-16 h-16 shrink-0 rounded-full bg-orange-50 border border-dashed border-orange-300 flex items-center justify-center cursor-pointer overflow-hidden text-xs text-orange-400 hover:bg-orange-100" title="写真を選ぶ">
                            <img id="child-photo-preview" class="hidden absolute inset-0 w-full h-full object-cover" alt="">
                            <span id="child-photo-placeholder">写真</span>
                            <input type="file" name="photo" accept="image/*" class="hidden">
                        </label>
                        <div class="flex-1">
                            <label class="block text-xs text-stone-500 mb-1">名前</label>
                            <input type="text" name="name" required autocomplete="off" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">生年月日</label>
                        <input type="date" name="birth_date" required class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <button type="submit" class="w-full py-2 rounded-md bg-orange-400 text-white text-sm font-medium hover:bg-orange-500">
                        追加する
                    </button>
                </form>
            </div>

            {{-- カレンダー --}}
            <div class="fd-card">
                <div class="flex items-center justify-between mb-3">
                    <button type="button" id="prev-month" class="px-2 py-1 rounded-md text-stone-400 hover:bg-stone-50">&lt;</button>
                    <h2 id="calendar-title" class="font-semibold text-stone-700"></h2>
                    <button type="button" id="next-month" class="px-2 py-1 rounded-md text-stone-400 hover:bg-stone-50">&gt;</button>
                </div>
                <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-center text-xs"></div>
            </div>

            {{-- 選択した日の記録 --}}
            <div class="fd-card">
                <div class="mb-3">
                    <h2 id="day-title" class="font-semibold text-stone-700">記録</h2>
                    <div class="flex items-center gap-4 mt-2">
                        <button type="button" id="toggle-diary-form" class="fd-pill fd-pill-orange">+ 日記を書く</button>
                        <button type="button" id="toggle-event-form" class="fd-pill fd-pill-blue">+ 予定を書く</button>
                    </div>
                </div>

                <form id="event-form" class="hidden mb-4 space-y-3 border-b border-orange-100 pb-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs text-stone-500 mb-1">だれの予定</label>
                            <div id="event-person-tabs" class="flex flex-wrap gap-2"></div>
                            <input type="hidden" id="event-person" name="person">
                        </div>
                        <div>
                            <label class="block text-xs text-stone-500 mb-1">種類</label>
                            <select name="event_type" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                                <option value="event">行事</option>
                                <option value="meeting">面談・保護者会</option>
                                <option value="kindergarten">幼稚園</option>
                                <option value="kindergarten_flat">幼稚園・フラット</option>
                                <option value="flat">フラット</option>
                                <option value="work">仕事</option>
                                <option value="off">非番</option>
                                <option value="holiday">休日</option>
                                <option value="lesson">習い事</option>
                                <option value="care">預かり</option>
                                <option value="other">その他</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">予定(任意)</label>
                        <input type="text" name="title" placeholder="例: 運動会、遠足、お弁当の日" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-xs text-stone-500 mb-1">メモ(任意)</label>
                        <textarea name="memo" rows="2" placeholder="例: 体操服・水筒を持っていく" class="w-full rounded-md border border-stone-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300"></textarea>
                    </div>
                    <button type="submit" class="w-full py-2 rounded-md bg-sky-500 text-white text-sm font-medium hover:bg-sky-600">
                        予定を保存する
                    </button>
                </form>

                <div id="school-events" class="space-y-3"></div>
                <div id="diary-entries" class="space-y-3 mt-3"></div>
                <p id="day-empty" class="text-sm text-stone-400">この日の予定はまだありません</p>
            </div>
        </section>
    </main>
</body>
</html>
