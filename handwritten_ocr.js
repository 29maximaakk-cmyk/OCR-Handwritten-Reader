// handwritten_ocr.js — JavaScript версия

const Tesseract = require('tesseract.js');
const fs = require('fs');
const path = require('path');

class HandwrittenOCR {
    constructor(lang = 'eng', psm = 6, oem = 3) {
        this.lang = lang;
        this.psm = psm;
        this.oem = oem;
        this.results = [];
    }

    async ocrImage(imagePath) {
        console.log(`📂 Обработка: ${path.basename(imagePath)}`);
        const start = Date.now();

        try {
            const result = await Tesseract.recognize(imagePath, this.lang, {
                logger: (m) => {
                    if (m.status === 'recognizing text') {
                        process.stderr.write(`\r⏳ Прогресс: ${Math.round(m.progress * 100)}%`);
                    }
                }
            });

            process.stderr.write('\n');
            const text = result.data.text.trim();
            const elapsed = (Date.now() - start) / 1000;

            this.printResult(text, elapsed);
            this.saveText(text, imagePath);

            return text;
        } catch (err) {
            console.error(`\x1b[31m❌ Ошибка: ${err.message}\x1b[0m`);
            return null;
        }
    }

    async ocrBatch(dir) {
        const exts = ['.jpg', '.jpeg', '.png', '.bmp', '.tiff', '.webp'];
        const files = fs.readdirSync(dir).filter(f => exts.includes(path.extname(f).toLowerCase()));

        if (files.length === 0) {
            console.log('❌ Нет поддерживаемых изображений.');
            return;
        }

        console.log(`📁 Найдено ${files.length} изображений.`);
        for (const file of files) {
            const filePath = path.join(dir, file);
            console.log(`\n[${files.indexOf(file)+1}/${files.length}] Обработка: ${file}`);
            const text = await this.ocrImage(filePath);
            if (text) {
                this.results.push({ file, text });
            }
        }

        this.saveJSON('ocr_results.json');
    }

    printResult(text, elapsed) {
        console.log(`\n\x1b[32mРезультат:\x1b[0m`);
        console.log('─'.repeat(50));
        console.log(text);
        console.log('─'.repeat(50));

        const words = text.split(/\s+/).filter(w => w).length;
        const chars = text.length;
        console.log(`\x1b[36m📊 Статистика:\x1b[0m`);
        console.log(`  Слов: ${words}`);
        console.log(`  Символов: ${chars}`);
        console.log(`  Время: ${elapsed.toFixed(2)} сек`);
    }

    saveText(text, imagePath) {
        const outputPath = path.basename(imagePath, path.extname(imagePath)) + '.txt';
        fs.writeFileSync(outputPath, text);
        console.log(`💾 Сохранено: ${outputPath}`);
    }

    saveJSON(filename) {
        fs.writeFileSync(filename, JSON.stringify(this.results, null, 2));
        console.log(`💾 Сохранено JSON: ${filename}`);
    }
}

async function main() {
    const args = process.argv.slice(2);
    let input = null;
    let lang = 'eng';
    let batch = false;
    let output = null;

    for (let i = 0; i < args.length; i++) {
        if (args[i] === '--lang' || args[i] === '-l') lang = args[++i];
        else if (args[i] === '--batch' || args[i] === '-b') batch = true;
        else if (args[i] === '--output' || args[i] === '-o') output = args[++i];
        else if (!input) input = args[i];
    }

    if (!input) {
        console.log('Usage: node handwritten_ocr.js <image> [--lang eng] [--batch]');
        process.exit(1);
    }

    console.log('\x1b[36m✍️ OCR Handwritten Reader (JavaScript)\x1b[0m');

    const ocr = new HandwrittenOCR(lang);

    if (batch || fs.statSync(input).isDirectory()) {
        await ocr.ocrBatch(input);
    } else {
        await ocr.ocrImage(input);
    }
}

main().catch(console.error);
