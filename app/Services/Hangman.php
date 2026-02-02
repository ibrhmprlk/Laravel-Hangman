<?php

namespace App\Services;

use Illuminate\Support\Arr;
use App\Models\HangmanQuestion;

class Hangman
{
    public string $originalWord;
    public string $cleanWord;
    public string $question;

    public array $guessedLetters = [];

    public int $maxAttempts = 6;
    public int $attemptsMade = 0;
    public int $hintAttemptsLeft = 3;

    public bool $wonByFullWord = false;

    public function __construct(
        ?string $word = null,
        ?string $question = null,
        array $guessedLetters = [],
        int $maxAttempts = 6,
        int $hintAttemptsLeft = 3,
        bool $wonByFullWord = false,
        int $attemptsMade = 0
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
        $this->guessedLetters = array_map([$this, 'normalize'], $guessedLetters);

        $this->maxAttempts = $maxAttempts;
        $this->hintAttemptsLeft = $hintAttemptsLeft;
        $this->wonByFullWord = $wonByFullWord;
        $this->attemptsMade = $attemptsMade;
    }

    /* -------------------- CORE -------------------- */

    public function normalize(string $text): string
    {
        return preg_replace(
            '/\s+/',
            '',
            str_replace(['İ', 'i'], ['I', 'I'], mb_strtoupper($text, 'UTF-8'))
        );
    }

    public function getMaskedWord(): string
    {
        if (empty($this->originalWord)) return '';

        $result = '';
        $chars = mb_str_split($this->originalWord, 1, 'UTF-8');

        foreach ($chars as $char) {
            $cleanChar = $this->normalize($char);

            if (trim($char) === '' || in_array($cleanChar, $this->guessedLetters)) {
                $result .= $char;
            } else {
                $result .= '_';
            }

            $result .= ' ';
        }

        return trim($result);
    }

    public function guessWord(string $guess): bool
    {
        if (empty($this->originalWord)) {
            throw new \Exception('Önce bir soru seçin!');
        }

        $cleanGuess = $this->normalize($guess);

        // Aynı tahmin tekrarlandıysa ceza verme
        if (in_array($cleanGuess, $this->guessedLetters)) {
            return true;
        }

        $this->guessedLetters[] = $cleanGuess;

        /* ---- TAM KELİME ---- */
        if (mb_strlen($cleanGuess) > 1) {
            if ($cleanGuess === $this->cleanWord) {
                $this->wonByFullWord = true;

                foreach (mb_str_split($this->cleanWord, 1, 'UTF-8') as $char) {
                    if (!in_array($char, $this->guessedLetters)) {
                        $this->guessedLetters[] = $char;
                    }
                }

                return true;
            }

            $this->attemptsMade++;
            return false;
        }

        /* ---- TEK HARF ---- */
        if (str_contains($this->cleanWord, $cleanGuess)) {
            return true;
        }

        $this->attemptsMade++;
        return false;
    }

    /* -------------------- HINT -------------------- */

 public function revealRandomLetters(int $count = 1): array
{
    if ($this->hintAttemptsLeft <= 0) return [];

    $this->hintAttemptsLeft--;

    $hidden = [];

    foreach (mb_str_split($this->cleanWord, 1, 'UTF-8') as $char) {
        if (!in_array($char, $this->guessedLetters)) {
            $hidden[] = $char;
        }
    }

    $hidden = array_values(array_unique($hidden));
    if (empty($hidden)) return [];

    $toReveal = (array) Arr::random($hidden, min($count, count($hidden)));

    foreach ($toReveal as $char) {
        $this->guessedLetters[] = $char;
    }

    return $toReveal;
}


    /* -------------------- STATE -------------------- */

    public function isWordGuessed(): bool
    {
        if (empty($this->originalWord)) return false;

        return $this->wonByFullWord || !str_contains($this->getMaskedWord(), '_');
    }

    public function isGameOver(): bool
    {
        return $this->attemptsMade >= $this->maxAttempts;
    }

    /* -------------------- GETTERS -------------------- */

    public function getWord(): string
    {
        return $this->originalWord;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function getGuessedLetters(): array
    {
        return $this->guessedLetters;
    }

    public function getRemainingAttempts(): int
    {
        return $this->maxAttempts - $this->attemptsMade;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getHintAttemptsLeft(): int
    {
        return $this->hintAttemptsLeft;
    }

    /* -------------------- SESSION -------------------- */

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
