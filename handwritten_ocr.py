
### 1. `handwritten_ocr.py` (Python)

```python
# handwritten_ocr.py — Python версия

import sys
import os
import time
import argparse
import json
from pathlib import Path
from PIL import Image
import pytesseract
from colorama import init, Fore, Style

init(autoreset=True)

class HandwrittenOCR:
    def __init__(self, lang='eng', config=None):
        self.lang = lang
        self.config = config or '--psm 6 --oem 3'
        self.results = []

    def ocr_image(self, image_path):
        """Распознаёт рукописный текст с изображения."""
        try:
            img = Image.open(image_path)
            # Предобработка для улучшения распознавания
            gray = img.convert('L')
            text = pytesseract.image_to_string(
                gray,
                lang=self.lang,
                config=self.config
            )
            return text.strip()
        except Exception as e:
            return f"❌ Ошибка: {e}"

    def ocr_batch(self, image_dir):
        """Пакетная обработка изображений."""
        extensions = ('.jpg', '.jpeg', '.png', '.bmp', '.tiff', '.tif', '.webp')
        files = [f for f in Path(image_dir).iterdir() if f.suffix.lower() in extensions]

        if not files:
            print(Fore.YELLOW + "Нет поддерживаемых изображений.")
            return

        print(f"📁 Найдено {len(files)} изображений.")
        for i, file in enumerate(files, 1):
            print(f"\n[{i}/{len(files)}] Обработка: {file.name}")
            text = self.ocr_image(str(file))
            self.results.append({'file': file.name, 'text': text})
            self.save_text(text, file.stem + '.txt')

    def save_text(self, text, filename):
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(text)
        print(f"💾 Сохранено: {filename}")

    def save_json(self, filename='ocr_results.json'):
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(self.results, f, indent=2, ensure_ascii=False)
        print(f"💾 Сохранено JSON: {filename}")

    def print_result(self, text, elapsed):
        print(f"\n{Fore.GREEN}Результат:{Style.RESET_ALL}")
        print("─" * 50)
        print(text)
        print("─" * 50)

        words = len(text.split())
        chars = len(text)
        print(f"{Fore.CYAN}📊 Статистика:{Style.RESET_ALL}")
        print(f"  Слов: {words}")
        print(f"  Символов: {chars}")
        print(f"  Время: {elapsed:.2f} сек")

def main():
    parser = argparse.ArgumentParser(description='Handwritten OCR Reader')
    parser.add_argument('input', help='Путь к изображению или папке')
    parser.add_argument('--lang', '-l', default='eng', help='Язык OCR (по умолчанию eng)')
    parser.add_argument('--psm', type=int, default=6, help='Режим сегментации (6 - блок текста)')
    parser.add_argument('--oem', type=int, default=3, help='Режим движка (3 - LSTM)')
    parser.add_argument('--batch', '-b', action='store_true', help='Пакетная обработка папки')
    parser.add_argument('--output', '-o', help='Файл для сохранения результата')
    args = parser.parse_args()

    print(Fore.CYAN + "✍️ OCR Handwritten Reader (Python)")

    config = f'--psm {args.psm} --oem {args.oem}'
    ocr = HandwrittenOCR(args.lang, config)

    if args.batch or os.path.isdir(args.input):
        ocr.ocr_batch(args.input)
        ocr.save_json()
    else:
        if not os.path.exists(args.input):
            print(Fore.RED + f"❌ Файл не найден: {args.input}")
            sys.exit(1)

        start = time.time()
        text = ocr.ocr_image(args.input)
        elapsed = time.time() - start

        ocr.print_result(text, elapsed)

        if args.output:
            ocr.save_text(text, args.output)
        else:
            default_output = os.path.splitext(args.input)[0] + '.txt'
            ocr.save_text(text, default_output)

if __name__ == "__main__":
    main()
