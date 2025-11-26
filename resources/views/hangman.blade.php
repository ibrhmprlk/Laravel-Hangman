<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Adam Asmaca</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            min-height: 100vh;
        }
        .hangman-gallows {
            width: 150px; max-width: 80vw; height: 200px; position: relative; margin: 20px auto;
            border-bottom: 4px solid #4a5568;
        }
        .gallows-vertical { position: absolute; top: 0; left: 10px; width: 4px; height: 100%; background-color: #4a5568; }
        .gallows-horizontal { position: absolute; top: 0; left: 10px; width: 100px; max-width: 60vw; height: 4px; background-color: #4a5568; }
        .gallows-rope { position: absolute; top: 4px; right: 40px; width: 4px; height: 20px; background-color: #4a5568; }
        @keyframes swing { 0% { transform: rotate(-10deg); } 50% { transform: rotate(10deg); } 100% { transform: rotate(-10deg); } }
        .animate-swing { animation: swing 1s infinite ease-in-out; transform-origin: top center; }
        @keyframes fireAnim { 0%,100% { transform: translateX(-50%) translateY(0) scaleY(1); opacity: 1; } 50% { transform: translateX(-50%) translateY(-8px) scaleY(1.3); opacity: 0.6; } }
        .animate-fire { animation: fireAnim 0.6s infinite alternate; }
        .animate-fire.delay-200 { animation-delay: 0.2s; }
        .animate-fire.delay-400 { animation-delay: 0.4s; }
        .glass-card {
            background: rgba(30, 30, 60, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(88, 101, 242, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        }
        img#asmaca { max-width: 80px; width: 20vw; height: auto; }
        #ataturk {
           opacity: 0;
    transition: opacity 2s ease-in-out;
        }
        #ismetYak {
            font-family: Impact, sans-serif;
            font-size: 36px;
            font-weight: bold;
            color: #ff0000;
            text-shadow: 0 0 20px #ffff00, 0 0 40px #ff0000;
            opacity: 0;
            transition: opacity 2s ease-in-out;
            letter-spacing: 4px;
        }

        /* MOBİL DÜZENLEMELER */
        @media (max-width: 640px) {
            .glass-card { padding: 6vw; }
            #asmaca { left: 50%; transform: translateX(-50%); }
            .hangman-gallows { height: 160px; }
            #questionList > div {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px;
                padding: 16px !important;
            }
            #questionList .flex-1 {
                width: 100%;
                padding-right: 0 !important;
            }
            #questionList .flex-1 > div {
                font-size: 1rem;
                white-space: normal !important;
                word-break: break-word;
            }
            #questionList button {
                flex: 1;
                padding: 12px 8px !important;
                font-size: 0.875rem !important;
                min-height: 44px;
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex flex-col items-center justify-center p-4 font-sans">
    <div class="w-full max-w-lg glass-card p-8 rounded-xl shadow-2xl border border-indigo-900/50">
        <h1 class="text-4xl font-bold text-center text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-orange-500 mb-6 tracking-widest drop-shadow-2xl">
            ADAM ASMACA
        </h1>
        <div id="messageArea">
            @if(isset($message))
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-3 rounded-lg text-center mb-4 text-sm font-bold shadow-lg">
                    {{ $message }}
                </div>
            @endif
        </div>

        <div class="text-center mb-8">
 <span class="font-bold text-yellow-300" id="question">
    @if($game->getQuestion())
        {{ $game->getQuestion() }}
    @else
        Henüz soru eklenmemiş. <strong>Yeni soru ekleyin!</strong>
    @endif
</span>

            <p class="text-5xl tracking-widest font-mono mb-4 text-yellow-400 drop-shadow-lg" id="maskedWord">
                {{ $game->getMaskedWord() }}
            </p>
            <p class="text-xl text-gray-300" id="attemptInfo">
                Kalan Hak: <span class="font-bold text-3xl text-red-500">{{ $game->getRemainingAttempts() }}</span> / {{ $game->getMaxAttempts() }}
            </p>
            <p class="text-sm text-gray-500 mt-2" id="guessedLetters">
                Tahminler: {{ implode(', ', $game->getGuessedLetters()) }}
            </p>
        </div>

        <div class="hangman-gallows mb-8">
            <div class="gallows-vertical"></div>
            <div class="gallows-horizontal"></div>
            <div class="gallows-rope"></div>
            <img id="asmaca" src="{{ asset('img/asmaca.jpg') }}" alt="asmaca"
                 class="absolute {{ !$game->isGameOver() && !$game->isWordGuessed() ? 'animate-swing' : '' }}"
                 style="left: 68px; top: 6px; transform-origin: top center;"
                 onerror="this.src='https://placehold.co/96x96/f00/fff?text=Adam'">
            <img id="ataturk" src="{{ asset('img/ATATÜRK.jpg') }}" alt="Atatürk" class="absolute"
                 style="right: -175px; top: 40px;" onerror="this.src='https://placehold.co/80x120/006400/fff?text=ATATÜRK'">
            <div id="ismetYak" class="absolute text-center font-bold"
                 style="right: -140px; top: 0; width: 110px; font-size: 16px;">İSMET YAK</div>
            <div id="ates" class="absolute bottom-0 left-1/2 transform -translate-x-1/3 w-40 h-20">
                <div class="relative w-full h-full">
                    <span class="absolute bottom-0 left-1/4 w-2 h-8 bg-yellow-400 rounded-full animate-fire"></span>
                    <span class="absolute bottom-0 left-1/2 w-2 h-8 bg-yellow-400 rounded-full animate-fire"></span>
                    <span class="absolute bottom-0 left-3/4 w-2 h-8 bg-yellow-400 rounded-full animate-fire"></span>
                    <span class="absolute bottom-0 left-1/3 w-1.5 h-6 bg-orange-500 rounded-full animate-fire delay-200"></span>
                    <span class="absolute bottom-0 left-2/3 w-1.5 h-6 bg-orange-500 rounded-full animate-fire delay-200"></span>
                    <span class="absolute bottom-0 left-1/4 w-1 h-4 bg-red-500 rounded-full animate-fire delay-400"></span>
                    <span class="absolute bottom-0 left-1/2 w-1 h-4 bg-red-500 rounded-full animate-fire delay-400"></span>
                    <span class="absolute bottom-0 left-3/4 w-1 h-4 bg-red-500 rounded-full animate-fire delay-400"></span>
                </div>
            </div>
        </div>

        <!-- EKLE VE SEÇ BUTONLARI - OYUN BİTTİĞİNDE GİZLENECEK -->
        <div id="addSelectButtons" class="grid grid-cols-2 gap-4 mb-6">
            <button id="toggleAddForm" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold py-4 rounded-xl shadow-xl transform hover:scale-105 transition">
                Yeni Kelime Ekle
            </button>
            <button id="toggleSelectForm" class="bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-500 hover:to-rose-500 text-white font-bold py-4 rounded-xl shadow-xl transform hover:scale-105 transition">
                Sorulardan Seç
            </button>
        </div>

        <form id="addForm" action="{{ route('hangman.add') }}" method="POST" class="hidden space-y-4 mb-6 bg-gray-800/70 p-6 rounded-xl border border-purple-500/30">
            @csrf
            <input type="text" name="question" placeholder="İpucu / Soru" required class="p-4 rounded-lg bg-gray-900/80 border border-gray-600 focus:border-purple-500 text-white w-full">
            <input type="text" name="answer" placeholder="Kelime / Cevap" required class="p-4 rounded-lg bg-gray-900/80 border border-gray-600 focus:border-purple-500 text-white w-full">
            <button type="submit" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 py-4 rounded-xl font-bold shadow-lg w-full">Ekle</button>
        </form>

        <div id="selectForm" class="hidden space-y-4 mb-6 bg-gray-800/70 p-6 rounded-xl border border-pink-500/30">
            <div class="bg-gray-900/60 border border-pink-600/50 rounded-xl p-4">
                <p class="text-pink-400 font-bold text-center mb-4">Soruları Yönet</p>
                <div id="questionList" class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($questions as $q)
                    <div class="flex items-center justify-between bg-gray-800/60 p-4 rounded-lg hover:bg-gray-700/80 transition" data-question-id="{{ $q->id }}">
                        <div class="flex-1 pr-4">
                            <div class="font-bold text-white">{{ $q->question }}</div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0">
                            <button type="button" class="select-question bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold px-6 py-2 rounded shadow-lg transition transform hover:scale-105" data-id="{{ $q->id }}">SEÇ</button>
                            <button type="button" class="delete-question bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2 rounded shadow-lg transition transform hover:scale-105" data-id="{{ $q->id }}">SİL</button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div id="mainGameControls">
            @if ($game->isWordGuessed())
                <div class="text-center p-6 bg-gradient-to-r from-emerald-600 to-green-600 rounded-xl text-2xl font-bold shadow-2xl">
                    TEBRİKLER! Kelimeyi bildiniz: {{ $game->getWord() }}
                </div>
                <a href="{{ route('hangman.reset') }}" class="block mt-4 text-center bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 py-4 rounded-xl font-bold shadow-xl">YENİ OYUN</a>
            @elseif ($game->isGameOver())
                <div class="text-center p-6 bg-gradient-to-r from-red-700 to-rose-700 rounded-xl text-2xl font-bold shadow-2xl">
                    OYUN BİTTİ! Kelime: {{ $game->getWord() }}
                </div>
                <a href="{{ route('hangman.reset') }}" class="block mt-4 text-center bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-400 hover:to-red-500 py-4 rounded-xl font-bold shadow-xl">TEKRAR DENE</a>
            @else
                <form id="guessForm" action="{{ route('hangman.guess') }}" method="POST" class="flex flex-col sm:flex-row gap-4 mt-6">
                    @csrf
                    <input type="text" name="guess_input" id="letterInput" placeholder="Harf veya Kelime Girin" required
                            class="flex-grow p-4 rounded-lg text-center font-bold uppercase bg-gray-800/80 border-2 border-gray-600 focus:border-cyan-500 w-full sm:w-auto">
                    <button type="submit" class="bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 py-4 px-8 rounded-lg font-bold shadow-xl transform hover:scale-105 transition">
                        Tahmin Et
                    </button>
                </form>
                <form id="hintForm" action="{{ route('hangman.hint') }}" method="GET" class="mt-4">
                    <button type="submit" id="hintButton" class="w-full bg-gradient-to-r from-amber-500 to-yellow-600 hover:from-amber-400 hover:to-yellow-500 py-4 rounded-lg font-bold shadow-xl disabled:opacity-50"
                            @if($game->getHintAttemptsLeft() <= 0) disabled @endif>
                        İpucu Al ({{ $game->getHintAttemptsLeft() }} kaldı)
                    </button>
                </form>
            @endif
            @if (!$game->isGameOver() && !$game->isWordGuessed())
                <div class="mt-6 text-center">
                    <a href="{{ route('hangman.reset') }}" class="text-gray-500 hover:text-red-400 text-sm underline">Oyunu Sıfırla</a>
                </div>
            @endif
        </div>
    </div>

<script>
// 3 SANİYE GÜZEL BİLDİRİM (KESİN GÖRÜNÜR!)
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.className = `fixed top-6 left-1/2 -translate-x-1/2 z-50 px-8 py-4 rounded-xl text-white font-bold text-lg shadow-2xl transition-all duration-500 ${type === 'success' ? 'bg-gradient-to-r from-emerald-600 to-green-600' : 'bg-gradient-to-r from-red-600 to-rose-600'}`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(-20px)';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

function showNotification(message, color) {
    document.getElementById('messageArea').innerHTML =
        `<div class="bg-gradient-to-r ${color === 'green' ? 'from-emerald-600 to-green-600' : 'from-red-600 to-rose-600'} p-3 rounded-lg text-center mb-4 text-sm font-bold shadow-lg">
            ${message}
        </div>`;
}

// Form aç/kapat
document.getElementById('toggleAddForm')?.addEventListener('click', () => {
    document.getElementById('addForm').classList.toggle('hidden');
    document.getElementById('selectForm').classList.add('hidden');
});
document.getElementById('toggleSelectForm')?.addEventListener('click', () => {
    document.getElementById('selectForm').classList.toggle('hidden');
    document.getElementById('addForm').classList.add('hidden');
});

// SİLME + 3 SANİYE BİLDİRİM
document.getElementById('questionList')?.addEventListener('click', function(e) {
    const target = e.target;

    if (target.classList.contains('delete-question')) {
        const id = target.getAttribute('data-id');
        if (!confirm('Bu soruyu KALICI olarak silmek istediğine emin misin?')) return;

        fetch("{{ route('hangman.delete') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({ id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Soru başarıyla silindi!', 'success');
                setTimeout(() => window.location.reload(), 2200);
            } else {
                alert(data.message || "Silinemedi!");
            }
        })
        .catch(() => showToast('Bağlantı hatası!', 'error'));
    }

    if (target.classList.contains('select-question')) {
        const id = target.getAttribute('data-id');
        fetch("{{ route('hangman.select') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: JSON.stringify({ question_id: id })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('question').textContent = data.question;
                updateGameDisplay({
                    maskedWord: data.maskedWord,
                    remainingAttempts: data.remainingAttempts,
                    maxAttempts: data.maxAttempts,
                    guessedLetters: data.guessedLetters || [],
                    message: data.message || "Oyun başladı!",
                    hintAttemptsLeft: data.hintAttemptsLeft || 2,
                    isGameOver: false,
                    isWordGuessed: false,
                    word: data.word
                });
                document.getElementById('selectForm').classList.add('hidden');
                hasDropped = false;
            } else {
                showNotification("Bu soru başlatılamadı!", "red");
            }
        })
        .catch(() => showNotification("Bağlantı hatası!", "red"));
    }
});

let hasDropped = false;

function updateGameDisplay(data) {
    document.getElementById('maskedWord').textContent = data.maskedWord;
    document.getElementById('attemptInfo').innerHTML = `Kalan Hak: <span class="font-bold text-3xl text-red-500">${data.remainingAttempts}</span> / ${data.maxAttempts}`;
    
    // TAHMİNLER DÜZGÜN GÖSTERİLSİN (sdfas da yazsın)
const guesses = Array.isArray(data.guessedLetters) && data.guessedLetters.length > 0
    ? data.guessedLetters.map(g => g.length > 1 ? `"${g}"` : g).join(', ')
    : 'Henüz yok';
document.getElementById('guessedLetters').textContent = `Tahminler: ${guesses}`;
    if (data.message) showNotification(data.message, data.message.includes('tebrik') || data.message.includes('doğru') ? 'green' : 'red');

    const hintLeft = data.hintAttemptsLeft ?? 0;
    const hintButton = document.getElementById('hintButton');
    if (hintButton) {
        hintButton.textContent = `İpucu Al (${hintLeft} kaldı)`;
        hintButton.disabled = hintLeft <= 0;
    }

    // OYUN BİTTİĞİNDE EKLE/SEÇ BUTONLARINI GİZLE
    if (data.isGameOver || data.isWordGuessed) {
        document.getElementById('addSelectButtons').style.display = 'none';
    }

    // Adam düşme animasyonu vs...
    if (data.isGameOver && !data.isWordGuessed && !hasDropped) {
        hasDropped = true;
        const adam = document.getElementById('asmaca');
        adam.classList.remove('animate-swing');
        adam.style.transition = 'transform 4s cubic-bezier(0.6, 0, 0.8, 0.2)';
        adam.style.transform = 'translateY(140px) translateX(20px) rotate(40deg)';
    }
    if (data.isWordGuessed && !hasDropped) {
        setTimeout(() => {
            document.getElementById('ataturk').style.opacity = '1';
            document.getElementById('ismetYak').style.opacity = '1';
            setTimeout(() => {
                hasDropped = true;
                const adam = document.getElementById('asmaca');
                adam.classList.remove('animate-swing');
                adam.style = 'transition: transform 3s cubic-bezier(0.6,0,0.8,0.2); transform: translateY(140px) translateX(20px) rotate(40deg);';
            }, 1200);
        }, 500);
    }

    if (data.isGameOver || data.isWordGuessed) {
        const html = data.isWordGuessed 
            ? `<div class="text-center p-6 bg-gradient-to-r from-emerald-600 to-green-600 rounded-xl text-2xl font-bold shadow-2xl">TEBRİKLER! Kelimeyi bildiniz: ${data.word}</div><a href="{{ route('hangman.reset') }}" class="block mt-4 text-center bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 py-4 rounded-xl font-bold shadow-xl">YENİ OYUN</a>`
            : `<div class="text-center p-6 bg-gradient-to-r from-red-700 to-rose-700 rounded-xl text-2xl font-bold shadow-2xl">OYUN BİTTİ! Kelime: ${data.word}</div><a href="{{ route('hangman.reset') }}" class="block mt-4 text-center bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-400 hover:to-red-500 py-4 rounded-xl font-bold shadow-xl">TEKRAR DENE</a>`;
        document.getElementById('mainGameControls').innerHTML = html;
    }
}

document.getElementById('guessForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const input = document.getElementById('letterInput');
    let val = input.value.trim();

    if (!val) {
        showNotification("Boş bırakamazsın!", "red");
        return;
    }

    // ARTIK HER ŞEY KABUL EDİLİR: sayı, kelime, boşluk, tırnak, ne istersen!
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ guess_input: val })  // ORİJİNAL HALİYLE GÖNDERİYORUZ
    })
    .then(r => r.json())
    .then(data => {
        updateGameDisplay(data);
        input.value = '';
    })
    .catch(err => {
        console.error(err);
        showToast('Bağlantı hatası!', 'error');
    });
});
// Diğer scriptler aynı kalıyor...
document.getElementById('hintForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(this.action, { method: 'GET' })
    .then(r => r.json())
    .then(data => updateGameDisplay(data));
});

document.getElementById('addForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch(this.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showNotification("Soru eklendi!", "green");
            const list = document.getElementById('questionList');
            const div = document.createElement('div');
            div.className = "flex items-center justify-between bg-gray-800/60 p-4 rounded-lg hover:bg-gray-700/80 transition";
            div.setAttribute('data-question-id', data.newQuestion.id);
            div.innerHTML = `<div class="flex-1 pr-4"><div class="font-bold text-white">${data.newQuestion.question}</div></div><div class="flex gap-2 flex-shrink-0"><button type="button" class="select-question bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold px-6 py-2 rounded shadow-lg transition transform hover:scale-105" data-id="${data.newQuestion.id}">SEÇ</button><button type="button" class="delete-question bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-2 rounded shadow-lg transition transform hover:scale-105" data-id="${data.newQuestion.id}">SİL</button></div>`;
            list.appendChild(div);
            this.reset();
            document.getElementById('addForm').classList.add('hidden');
        }
    });
});
</script>
</body>
</html>
