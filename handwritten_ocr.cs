// handwritten_ocr.cs — C# версия

using System;
using System.IO;
using System.Text.Json;
using System.Collections.Generic;
using System.Linq;
using Tesseract;

class HandwrittenOCR {
    static void Main(string[] args) {
        string imagePath = null;
        string lang = "eng";
        bool batch = false;
        string output = null;

        for (int i = 0; i < args.Length; i++) {
            if (args[i] == "--lang" || args[i] == "-l") {
                lang = args[++i];
            } else if (args[i] == "--batch" || args[i] == "-b") {
                batch = true;
            } else if (args[i] == "--output" || args[i] == "-o") {
                output = args[++i];
            } else if (imagePath == null) {
                imagePath = args[i];
            }
        }

        if (imagePath == null) {
            Console.WriteLine("Usage: dotnet run <image> [--lang eng] [--batch]");
            return;
        }

        Console.WriteLine("\u001B[36m✍️ OCR Handwritten Reader (C#)\u001B[0m");

        using (var engine = new TesseractEngine("./tessdata", lang, EngineMode.Default)) {
            engine.DefaultPageSegMode = PageSegMode.SingleBlock;
            if (batch || Directory.Exists(imagePath)) {
                ProcessBatch(engine, imagePath);
            } else {
                ProcessSingle(engine, imagePath, output);
            }
        }
    }

    static void ProcessSingle(TesseractEngine engine, string imagePath, string output) {
        var start = DateTime.Now;
        using (var img = Pix.LoadFromFile(imagePath)) {
            using (var page = engine.Process(img)) {
                string text = page.GetText().Trim();
                var elapsed = (DateTime.Now - start).TotalSeconds;

                PrintResult(text, elapsed);

                if (output == null) {
                    output = Path.GetFileNameWithoutExtension(imagePath) + ".txt";
                }
                File.WriteAllText(output, text);
                Console.WriteLine($"💾 Сохранено: {output}");
            }
        }
    }

    static void ProcessBatch(TesseractEngine engine, string dir) {
        var exts = new[] { ".jpg", ".jpeg", ".png", ".bmp", ".tiff" };
        var files = Directory.GetFiles(dir).Where(f => exts.Contains(Path.GetExtension(f).ToLower())).ToList();

        if (files.Count == 0) {
            Console.WriteLine("❌ Нет поддерживаемых изображений.");
            return;
        }

        Console.WriteLine($"📁 Найдено {files.Count} изображений.");
        var results = new List<Dictionary<string, string>>();

        for (int i = 0; i < files.Count; i++) {
            Console.WriteLine($"\n[{i+1}/{files.Count}] Обработка: {Path.GetFileName(files[i])}");
            using (var img = Pix.LoadFromFile(files[i])) {
                using (var page = engine.Process(img)) {
                    string text = page.GetText().Trim();
                    string outFile = Path.GetFileNameWithoutExtension(files[i]) + ".txt";
                    File.WriteAllText(outFile, text);
                    Console.WriteLine($"💾 Сохранено: {outFile}");
                    results.Add(new Dictionary<string, string> { ["file"] = Path.GetFileName(files[i]), ["text"] = text });
                }
            }
        }

        string json = JsonSerializer.Serialize(results, new JsonSerializerOptions { WriteIndented = true });
        File.WriteAllText("ocr_results.json", json);
        Console.WriteLine($"\n💾 Сохранено JSON: ocr_results.json");
    }

    static void PrintResult(string text, double elapsed) {
        Console.WriteLine($"\n\u001B[32mРезультат:\u001B[0m");
        Console.WriteLine(new string('─', 50));
        Console.WriteLine(text);
        Console.WriteLine(new string('─', 50));

        var words = text.Split(new[] { ' ', '\n' }, StringSplitOptions.RemoveEmptyEntries).Length;
        Console.WriteLine($"\u001B[36m📊 Статистика:\u001B[0m");
        Console.WriteLine($"  Слов: {words}");
        Console.WriteLine($"  Символов: {text.Length}");
        Console.WriteLine($"  Время: {elapsed:F2} сек");
    }
}
