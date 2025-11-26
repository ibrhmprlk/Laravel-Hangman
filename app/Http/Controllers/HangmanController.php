<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\Hangman;
use App\Models\HangmanQuestion;
use Illuminate\Http\JsonResponse;

class HangmanController extends Controller
{
    /**
     * Sadece mevcut oturumdaki oyunu yükler. Oturum boşsa null döndürür.
     */
private function getExistingGame(): ?Hangman
{
    $sessionData = Session::get('hangman_game');

    if ($sessionData) {
        return new Hangman(
            $sessionData['word'] ?? null,
            $sessionData['question'] ?? null,
            $sessionData['guessed'] ?? [],
            3,
            $sessionData['hintAttemptsLeft'] ?? 3,
            $sessionData['wonByFullWord'] ?? false  // YENİ EKLENDİ!
        );
    }

    return null;
}
    /**
     * Ana sayfa. Her zaman yeni ve rastgele bir oyun başlatmayı garanti eder.
     */
    public function index()
    {
        // YENİ MANTIK: Oturumu tamamen temizle 
        Session::forget('hangman_game'); 
        
        // YENİ MANTIK: Her zaman rastgele yeni bir oyun nesnesi oluştur
        $game = new Hangman(); 
        
        $questions = HangmanQuestion::select('id', 'question')->get();
        
        // Yeni oyunun durumunu Session'a kaydet
        Session::put('hangman_game', $game->toSessionArray());

        // Mesajı çek (varsa)
        $message = Session::pull('message');

        return view('hangman', [
            'game' => $game,
            'questions' => $questions, 
            'message' => $message
        ]);
    }
public function deleteQuestion(Request $request)
{
    $id = $request->input('id');
    
    $question = HangmanQuestion::find($id);
    
    if (!$question) {
        return response()->json(['success' => false, 'message' => 'Soru bulunamadı!']);
    }

    // Bu çok önemli: Silinen sorunun textini frontend'e gönderiyoruz
    $deletedQuestionText = $question->question;

    $question->delete();

    return response()->json([
        'success' => true,
        'deleted_question' => $deletedQuestionText,  // BU SATIR YENİ
        'message' => '"' . $deletedQuestionText . '" sorusu silindi!'
    ]);
}
 public function selectQuestion(Request $request): JsonResponse
{
    $request->validate(['question_id' => 'required|integer']);
    $question = HangmanQuestion::findOrFail($request->question_id);

    $game = new Hangman($question->answer, $question->question);
    Session::put('hangman_game', $game->toSessionArray());

    return response()->json([
        'success'     => true,
        'message'     => "Yeni oyun başlatıldı: '{$question->question}'",
      
        'maskedWord'  => $game->getMaskedWord(),
        'question'    => $game->getQuestion(),
        'remainingAttempts' => $game->getRemainingAttempts(),
        'maxAttempts' => $game->getMaxAttempts(),
       'guessedLetters' => $game->getGuessedLetters(),
        'hintAttemptsLeft'   => $game->getHintAttemptsLeft(),
        'isGameOver'  => false,
        'isWordGuessed' => false,
    ]);
}
    /**
     * HARF VEYA KELİME TAHMİNİ METODU (AJAX UYUMLU)
     */
public function guess(Request $request): JsonResponse
{
    $rawInput = trim($request->input('guess_input') ?? '');
    if (empty($rawInput)) {
        return response()->json(['message' => 'Boş olamaz!'], 400);
    }

    $game = $this->getExistingGame() ?? new Hangman();
    $correct = $game->guessWord($rawInput);

    $message = $game->normalize($rawInput) === $game->cleanWord
        ? "TEBRİKLER! Kelimeyi bildin!"
        : ($correct ? "\"$rawInput\" doğru!" : "\"$rawInput\" yanlış! Kalan hak: {$game->getRemainingAttempts()}");

    Session::put('hangman_game', $game->toSessionArray());

    return response()->json([
        'maskedWord'        => $game->getMaskedWord(),
        'remainingAttempts' => $game->getRemainingAttempts(),
        'maxAttempts'       => $game->getMaxAttempts(),
        'guessedLetters'    => $game->getGuessedLetters(), // TÜM TAHMİNLER!
        'isGameOver'        => $game->isGameOver(),
        'isWordGuessed'     => $game->isWordGuessed(),
        'word'              => $game->getWord(),
        'message'           => $message,
        'hintAttemptsLeft'  => $game->getHintAttemptsLeft(),
    ]);
}
    /**
     * İPUCU METODU (AJAX UYUMLU)
     */
    public function hint(): JsonResponse
    {
        // Oyun mevcut değilse rastgele yeni oyun başlatılır
        $game = $this->getExistingGame() ?? new Hangman(); 
        $message = '';

        if ($game->getHintAttemptsLeft() > 0) {
            $revealed = $game->revealRandomLetters(1);
            $message = 'İpucu: Harf(ler) ortaya çıktı: ' . implode(', ', $revealed);
        } else {
            $message = 'İpucu hakkınız kalmadı!';
        }

        Session::put('hangman_game', $game->toSessionArray());
        
        // JSON yanıtı döndür
        return response()->json([
            'maskedWord' => $game->getMaskedWord(),
            'remainingAttempts' => $game->getRemainingAttempts(),
            'maxAttempts' => $game->getMaxAttempts(),
       'guessedLetters' => $game->getGuessedLetters(),
            'isGameOver' => $game->isGameOver(),
            'isWordGuessed' => $game->isWordGuessed(),
            'word' => $game->getWord(),
            'message' => $message,
            'hintAttemptsLeft' => $game->getHintAttemptsLeft(),
        ]);
    }
public function reset()
{
    Session::forget('hangman_game');
    
    // %100 yeni rastgele oyun
    $game = new Hangman();
    Session::put('hangman_game', $game->toSessionArray());
    
    Session::flash('message', 'Yeni rastgele oyun başlatıldı!');
    return redirect()->route('hangman.index');
}

public function add(Request $request)
{
    $request->validate([
        'question' => 'required|string|max:255',
        'answer'   => 'required|string|max:255',
    ]);

    $originalAnswer = $request->answer; // Kullanıcının yazdığı hali (boşluklu)
    $cleanAnswer = strtoupper(str_replace(' ', '', $request->answer)); // Boşluksuz, büyük harf

    $created = HangmanQuestion::create([
        'question' => $request->question,
        'answer'   => $cleanAnswer, // DB'ye temiz hali
    ]);

    if ($request->ajax() || $request->wantsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Yeni kelime ve ipucu eklendi!',
            'newQuestion' => [
                'id'       => $created->id,
                'question' => $created->question,
                'answer'   => $originalAnswer, // BURASI ÇOK ÖNEMLİ! Kullanıcının yazdığı orijinal hali
            ]
        ]);
    }

    return redirect()->back()->with('message', 'Yeni kelime ve ipucu eklendi!');
}
}