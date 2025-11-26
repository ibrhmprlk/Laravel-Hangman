<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Models\HangmanQuestion;

class Hangman
{
    public string $originalWord;     // public yaptık
    public string $cleanWord;        // public yaptık
    public string $question;         // public yaptık
    public array $guessedLetters = [];
    public int $maxAttempts = 6;
    public int $attemptsMade = 0;
    public int $hintAttemptsLeft = 3;
    public bool $wonByFullWord = false;

    public function __construct(
        ?string $word = null,
        ?string $question = null,
        array $guessedLetters = [],
        int $maxAttempts = 3,
        int $hintAttemptsLeft = 3,
        bool $wonByFullWord = false
    ) {
       if (!$word || !$question) {
    $random = HangmanQuestion::inRandomOrder()->first();
    $this->originalWord = $random?->answer ?? '';
    $this->question = $random?->question ?? 'Henüz soru eklenmedi';
} else {
    $this->originalWord = $word;
    $this->question = $question;
}


        $this->cleanWord = $this->normalize($this->originalWord);
        $this->guessedLetters = array_map('mb_strtoupper', $guessedLetters);
        $this->maxAttempts = $maxAttempts;
        $this->hintAttemptsLeft = $hintAttemptsLeft;
        $this->wonByFullWord = $wonByFullWord;
        $this->attemptsMade = $this->calculateWrongAttempts();
    }

    // normalize artık PUBLIC ve basit
    public function normalize(string $text): string
    {
        return preg_replace('/\s+/', '', str_replace(['İ', 'i'], ['I', 'I'], mb_strtoupper($text, 'UTF-8')));
    }

  public function getMaskedWord(): string
{
    if (empty($this->originalWord)) {
        return ''; // veya 'Henüz kelime eklenmedi'
    }

    $result = '';
    $chars = mb_str_split($this->originalWord, 1, 'UTF-8');

    foreach ($chars as $char) {
        $cleanChar = $this->normalize($char);
        if (in_array($cleanChar, $this->guessedLetters) || trim($char) === '') {
            $result .= $char;
        } else {
            $result .= '_';
        }
        $result .= ' ';
    }
    return trim($result);
}
    // ASIL KULLANILAN METOD – HER ŞEY BURADAN GEÇER
public function guessWord(string $guess): bool
{
    // Eğer kelime yoksa tahmin yapılamaz
    if (empty($this->originalWord)) {
        throw new \Exception("Önce bir soru seçin veya ekleyin!");
    }

    $cleanGuess = $this->normalize($guess);

    if (!in_array($guess, $this->guessedLetters)) {
        $this->guessedLetters[] = $guess;
    }

    // TAM KELİME EŞLEŞMESİ
    if ($cleanGuess === $this->cleanWord) {
        $this->wonByFullWord = true;
        foreach (mb_str_split($this->cleanWord, 1, 'UTF-8') as $char) {
            if (!in_array($char, $this->guessedLetters)) {
                $this->guessedLetters[] = $char;
            }
        }
        return true;
    }

    // Kısmi eşleşme → harfleri aç
    if (str_contains($this->cleanWord, $cleanGuess)) {
        foreach (mb_str_split($cleanGuess, 1, 'UTF-8') as $char) {
            if (!in_array($char, $this->guessedLetters)) {
                $this->guessedLetters[] = $char;
            }
        }
        return true;
    }

    // Yanlış tahmin → hak düş
    $this->attemptsMade++;
    return false;
}

    private function calculateWrongAttempts(): int
    {
        $wrong = 0;
        foreach ($this->guessedLetters as $g) {
            $cleanG = $this->normalize($g);
            if (mb_strlen($cleanG) === 1 && !str_contains($this->cleanWord, $cleanG)) {
                $wrong++;
            } elseif (mb_strlen($cleanG) > 1 && $cleanG !== $this->cleanWord && !str_contains($this->cleanWord, $cleanG)) {
                $wrong++;
            }
        }
        return $wrong;
    }

    public function revealRandomLetters(int $count = 1): array
    {
        if ($this->hintAttemptsLeft <= 0) return [];
        $this->hintAttemptsLeft--;

        $hidden = [];
        foreach (mb_str_split($this->cleanWord, 1, 'UTF-8') as $char) {
            if (!in_array($char, $this->guessedLetters)) $hidden[] = $char;
        }
        $hidden = array_unique($hidden);
        if (empty($hidden)) return [];

        $toReveal = Arr::random($hidden, min($count, count($hidden)));
        foreach ($toReveal as $char) {
            $this->guessedLetters[] = $char;
        }
        return $toReveal;
    }

    public function isWordGuessed(): bool
{
    if (empty($this->originalWord)) {
        return false; // kelime yoksa kazandınız mesajı gelmesin
    }

    return $this->wonByFullWord || !str_contains($this->getMaskedWord(), '_');
}

    public function isGameOver(): bool
    {
        return $this->attemptsMade >= $this->maxAttempts;
    }

    // Getters
    public function getWord(): string { return $this->originalWord; }
   public function getQuestion(): string {
    return $this->question;
}


    public function getGuessedLetters(): array { return $this->guessedLetters; }
    public function getRemainingAttempts(): int { return $this->maxAttempts - $this->attemptsMade; }
    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function getHintAttemptsLeft(): int { return $this->hintAttemptsLeft; }

    public function toSessionArray(): array
    {
        return [
            'word' => $this->originalWord,
            'question' => $this->question,
            'guessed' => $this->guessedLetters,
            'attempts' => $this->attemptsMade,
            'hintAttemptsLeft' => $this->hintAttemptsLeft,
            'wonByFullWord' => $this->wonByFullWord,
        ];
    }
}
