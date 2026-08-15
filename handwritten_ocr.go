// handwritten_ocr.go — Go версия

package main

import (
	"encoding/json"
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"

	"github.com/otiai10/gosseract/v2"
)

type OCRResult struct {
	File string `json:"file"`
	Text string `json:"text"`
}

func main() {
	lang := flag.String("lang", "eng", "Язык OCR")
	psm := flag.Int("psm", 6, "Режим сегментации")
	oem := flag.Int("oem", 3, "Режим движка")
	batch := flag.Bool("batch", false, "Пакетная обработка")
	output := flag.String("output", "", "Файл для сохранения")
	flag.Parse()

	if flag.NArg() < 1 {
		fmt.Println("Usage: go run handwritten_ocr.go <image> [--lang eng] [--batch]")
		os.Exit(1)
	}

	input := flag.Arg(0)
	fmt.Println("\x1b[36m✍️ OCR Handwritten Reader (Go)\x1b[0m")

	client := gosseract.NewClient()
	defer client.Close()

	// Настройка для рукописного текста
	client.SetLanguage(*lang)
	client.SetPageSegMode(gosseract.PSM_SINGLE_BLOCK)
	client.SetOcrEngineMode(gosseract.OEM_LSTM_ONLY)

	if *batch || isDir(input) {
		processBatch(client, input)
	} else {
		processSingle(client, input, *output)
	}
}

func processSingle(client *gosseract.Client, imagePath, output string) {
	start := time.Now()
	client.SetImage(imagePath)
	text, err := client.Text()
	if err != nil {
		fmt.Printf("\x1b[31m❌ Ошибка: %v\x1b[0m\n", err)
		os.Exit(1)
	}
	elapsed := time.Since(start).Seconds()

	printResult(text, elapsed)

	if output == "" {
		output = strings.TrimSuffix(imagePath, filepath.Ext(imagePath)) + ".txt"
	}
	os.WriteFile(output, []byte(text), 0644)
	fmt.Printf("💾 Сохранено: %s\n", output)
}

func processBatch(client *gosseract.Client, dir string) {
	exts := map[string]bool{".jpg": true, ".jpeg": true, ".png": true, ".bmp": true, ".tiff": true}
	files := []string{}
	entries, _ := os.ReadDir(dir)
	for _, e := range entries {
		if !e.IsDir() {
			ext := strings.ToLower(filepath.Ext(e.Name()))
			if exts[ext] {
				files = append(files, filepath.Join(dir, e.Name()))
			}
		}
	}

	if len(files) == 0 {
		fmt.Println("❌ Нет поддерживаемых изображений.")
		return
	}

	fmt.Printf("📁 Найдено %d изображений.\n", len(files))
	results := []OCRResult{}

	for i, f := range files {
		fmt.Printf("\n[%d/%d] Обработка: %s\n", i+1, len(files), filepath.Base(f))
		client.SetImage(f)
		text, _ := client.Text()
		results = append(results, OCRResult{File: filepath.Base(f), Text: text})
		os.WriteFile(strings.TrimSuffix(f, filepath.Ext(f))+".txt", []byte(text), 0644)
	}

	jsonData, _ := json.MarshalIndent(results, "", "  ")
	os.WriteFile("ocr_results.json", jsonData, 0644)
	fmt.Println("\n💾 Сохранено JSON: ocr_results.json")
}

func printResult(text string, elapsed float64) {
	fmt.Printf("\n\x1b[32mРезультат:\x1b[0m\n")
	fmt.Println(strings.Repeat("─", 50))
	fmt.Println(text)
	fmt.Println(strings.Repeat("─", 50))

	words := len(strings.Fields(text))
	chars := len(text)
	fmt.Printf("\x1b[36m📊 Статистика:\x1b[0m\n")
	fmt.Printf("  Слов: %d\n", words)
	fmt.Printf("  Символов: %d\n", chars)
	fmt.Printf("  Время: %.2f сек\n", elapsed)
}

func isDir(path string) bool {
	info, err := os.Stat(path)
	return err == nil && info.IsDir()
}
