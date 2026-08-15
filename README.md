# ✍️ OCR Handwritten Reader — расшифровывай рукописный текст за секунду

> «Рукопись — не проблема, если есть правильный OCR»

**OCR Handwritten Reader** — это набор консольных утилит для распознавания рукописного текста с изображений с помощью Tesseract OCR.  
Программа использует специальные модели для рукописного текста (например, `eng` с параметром `--psm 6` или `--oem 3`), поддерживает английский, русский и другие языки.

## 🚀 Особенности
- ✍️ Специализированная настройка Tesseract для рукописного текста (PSM 6, OEM 3).
- 📷 Поддержка форматов: JPG, PNG, BMP, TIFF, PDF.
- 🌍 Поддержка 100+ языков (английский, русский, французский, немецкий и др.).
- 🎨 Цветной вывод в терминале.
- 📤 Экспорт результата в TXT, JSON и CSV.
- 📁 Пакетная обработка изображений.
- 🎯 Автоматическое определение ориентации текста.
- ⚡ Прогресс-бар для больших файлов.

## 🛠️ Установка и запуск

Для работы требуется **Tesseract OCR** с языковыми пакетами.

### Установка Tesseract

| OS | Команда |
|----|---------|
| **Linux (Ubuntu/Debian)** | `sudo apt install tesseract-ocr tesseract-ocr-rus tesseract-ocr-eng` |
| **macOS (Homebrew)** | `brew install tesseract` |
| **Windows** | Скачайте с [GitHub](https://github.com/UB-Mannheim/tesseract/wiki) |

### Запуск

Для каждого языка — минимальные зависимости.

| Язык       | Зависимости                          | Команда запуска                         |
|------------|--------------------------------------|-----------------------------------------|
| Python     | `pytesseract`, `Pillow`, `opencv-python` | `python handwritten_ocr.py image.png` |
| Go         | `gosseract`                          | `go run handwritten_ocr.go image.png`   |
| JavaScript | `tesseract.js`                       | `node handwritten_ocr.js image.png`     |
| Java       | `Tess4J`                             | `javac -cp .:tess4j.jar ... && java ...`|
| C#         | `Tesseract.NET`                      | `dotnet run image.png`                  |
| Rust       | `rusty-tesseract`                    | `cargo run -- image.png`                |
| Ruby       | `rtesseract`                         | `ruby handwritten_ocr.rb image.png`     |
| PHP        | `thiagoalessio/tesseract_ocr`        | `php handwritten_ocr.php image.png`     |

## 📖 Пример использования

```bash
$ python handwritten_ocr.py note.jpg --lang eng --batch
Вывод:

text
✍️ OCR Handwritten Reader (Python)
📂 Обработка: note.jpg
⏳ Распознавание рукописного текста...

Результат:
─────────────────────────────────────────
The quick brown fox jumps over the lazy dog.
This is a sample of handwritten text recognition.

─────────────────────────────────────────
📊 Статистика:
  Слов: 16
  Символов: 72
  Время: 1.23 сек
💾 Сохранено: note.txt
💾 Сохранено: note.json
🤝 Вклад
Принимаются улучшения, новые языки, фичи.

📜 Лицензия
MIT — используйте свободно.

Автор: Ваш покорный слуга
