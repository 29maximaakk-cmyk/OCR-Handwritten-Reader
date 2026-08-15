<?php
// handwritten_ocr.php — PHP версия

require_once 'vendor/autoload.php';

use thiagoalessio\TesseractOCR\TesseractOCR;

class HandwrittenOCR {
    private $lang;
    private $psm;
    private $oem;
    private $results = [];

    public function __construct($lang = 'eng', $psm = 6, $oem = 3) {
        $this->lang = $lang;
        $this->psm = $psm;
        $this->oem = $oem;
    }

    public function ocrImage($imagePath) {
        echo "📂 Обработка: " . basename($imagePath) . "\n";
        $start = microtime(true);

        try {
            $ocr = new TesseractOCR($imagePath);
            $ocr->lang($this->lang);
            $ocr->psm($this->psm);
            $ocr->oem($this->oem);
            $text = trim((string) $ocr);
            $elapsed = microtime(true) - $start;

            $this->printResult($text, $elapsed);
            $this->saveText($text, $imagePath);
            return $text;
        } catch (Exception $e) {
            echo "\033[31m❌ Ошибка: " . $e->getMessage() . "\033[0m\n";
            return null;
        }
    }

    public function ocrBatch($dir) {
        $exts = ['jpg', 'jpeg', 'png', 'bmp', 'tiff', 'webp'];
        $files = array_diff(scandir($dir), ['.', '..']);
        $images = [];
        foreach ($files as $f) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $exts)) {
                $images[] = $f;
            }
        }

        if (empty($images)) {
            echo "❌ Нет поддерживаемых изображений.\n";
            return;
        }

        echo "📁 Найдено " . count($images) . " изображений.\n";
        foreach ($images as $i => $f) {
            echo "\n[" . ($i+1) . "/" . count($images) . "] Обработка: $f\n";
            $text = $this->ocrImage($dir . DIRECTORY_SEPARATOR . $f);
            if ($text) {
                $this->results[] = ['file' => $f, 'text' => $text];
            }
        }

        $this->saveJSON('ocr_results.json');
    }

    private function printResult($text, $elapsed) {
        echo "\n\033[32mРезультат:\033[0m\n";
        echo str_repeat("─", 50) . "\n";
        echo $text . "\n";
        echo str_repeat("─", 50) . "\n";

        $words = count(array_filter(explode(' ', $text)));
        $chars = strlen($text);
        echo "\033[36m📊 Статистика:\033[0m\n";
        echo "  Слов: $words\n";
        echo "  Символов: $chars\n";
        echo "  Время: " . number_format($elapsed, 2) . " сек\n";
    }

    private function saveText($text, $imagePath) {
        $outFile = pathinfo($imagePath, PATHINFO_FILENAME) . '.txt';
        file_put_contents($outFile, $text);
        echo "💾 Сохранено: $outFile\n";
    }

    private function saveJSON($filename) {
        file_put_contents($filename, json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "💾 Сохранено JSON: $filename\n";
    }
}

function main($argv) {
    $input = null;
    $lang = 'eng';
    $batch = false;
    $output = null;

    for ($i = 1; $i < count($argv); $i++) {
        if ($argv[$i] == '--lang' || $argv[$i] == '-l') {
            $lang = $argv[++$i];
        } elseif ($argv[$i] == '--batch' || $argv[$i] == '-b') {
            $batch = true;
        } elseif ($argv[$i] == '--output' || $argv[$i] == '-o') {
            $output = $argv[++$i];
        } elseif ($input === null) {
            $input = $argv[$i];
        }
    }

    if ($input === null) {
        echo "Usage: php handwritten_ocr.php <image> [--lang eng] [--batch]\n";
        exit(1);
    }

    echo "\033[36m✍️ OCR Handwritten Reader (PHP)\033[0m\n";

    $ocr = new HandwrittenOCR($lang);

    if ($batch || is_dir($input)) {
        $ocr->ocrBatch($input);
    } else {
        if (!file_exists($input)) {
            echo "\033[31m❌ Файл не найден: $input\033[0m\n";
            exit(1);
        }
        $ocr->ocrImage($input);
    }
}

$argc = $_SERVER['argc'] ?? 0;
$argv = $_SERVER['argv'] ?? [];
main($argv);
?>
