// handwritten_ocr.java — Java версия

import net.sourceforge.tess4j.Tesseract;
import net.sourceforge.tess4j.TesseractException;
import java.io.*;
import java.nio.file.*;
import java.util.*;

public class handwritten_ocr {
    public static void main(String[] args) throws Exception {
        String imagePath = null;
        String lang = "eng";
        boolean batch = false;
        String output = null;

        for (int i = 0; i < args.length; i++) {
            if (args[i].equals("--lang") || args[i].equals("-l")) {
                lang = args[++i];
            } else if (args[i].equals("--batch") || args[i].equals("-b")) {
                batch = true;
            } else if (args[i].equals("--output") || args[i].equals("-o")) {
                output = args[++i];
            } else if (imagePath == null) {
                imagePath = args[i];
            }
        }

        if (imagePath == null) {
            System.out.println("Usage: java handwritten_ocr <image> [--lang eng] [--batch]");
            System.exit(1);
        }

        System.out.println("\u001B[36m✍️ OCR Handwritten Reader (Java)\u001B[0m");

        Tesseract tesseract = new Tesseract();
        tesseract.setLanguage(lang);
        tesseract.setPageSegMode(6); // Single block of text
        tesseract.setOcrEngineMode(3); // LSTM

        if (batch || Files.isDirectory(Paths.get(imagePath))) {
            processBatch(tesseract, imagePath);
        } else {
            processSingle(tesseract, imagePath, output);
        }
    }

    private static void processSingle(Tesseract tesseract, String imagePath, String output) throws TesseractException {
        long start = System.currentTimeMillis();
        String text = tesseract.doOCR(new File(imagePath));
        double elapsed = (System.currentTimeMillis() - start) / 1000.0;

        printResult(text, elapsed);

        if (output == null) {
            output = imagePath.replaceFirst("\\.[^.]+$", "") + ".txt";
        }
        try (FileWriter fw = new FileWriter(output)) {
            fw.write(text);
        } catch (IOException e) {
            System.out.println("Ошибка сохранения.");
        }
        System.out.println("💾 Сохранено: " + output);
    }

    private static void processBatch(Tesseract tesseract, String dir) throws TesseractException {
        String[] exts = {".jpg", ".jpeg", ".png", ".bmp", ".tiff"};
        File folder = new File(dir);
        List<File> files = new ArrayList<>();
        for (File f : folder.listFiles()) {
            if (f.isFile()) {
                String ext = f.getName().toLowerCase();
                for (String e : exts) {
                    if (ext.endsWith(e)) files.add(f);
                }
            }
        }

        if (files.isEmpty()) {
            System.out.println("❌ Нет поддерживаемых изображений.");
            return;
        }

        System.out.println("📁 Найдено " + files.size() + " изображений.");
        for (int i = 0; i < files.size(); i++) {
            System.out.printf("\n[%d/%d] Обработка: %s\n", i+1, files.size(), files.get(i).getName());
            String text = tesseract.doOCR(files.get(i));
            String outFile = files.get(i).getName().replaceFirst("\\.[^.]+$", "") + ".txt";
            try (FileWriter fw = new FileWriter(outFile)) {
                fw.write(text);
            } catch (IOException e) {}
            System.out.println("💾 Сохранено: " + outFile);
        }
    }

    private static void printResult(String text, double elapsed) {
        System.out.println("\n\u001B[32mРезультат:\u001B[0m");
        System.out.println("─".repeat(50));
        System.out.println(text);
        System.out.println("─".repeat(50));

        String[] words = text.split("\\s+");
        int wordCount = text.isEmpty() ? 0 : words.length;
        int charCount = text.length();
        System.out.println("\u001B[36m📊 Статистика:\u001B[0m");
        System.out.println("  Слов: " + wordCount);
        System.out.println("  Символов: " + charCount);
        System.out.println("  Время: " + String.format("%.2f", elapsed) + " сек");
    }
}
