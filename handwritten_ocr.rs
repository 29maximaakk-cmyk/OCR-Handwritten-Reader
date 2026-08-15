// handwritten_ocr.rs — Rust версия

use rusty_tesseract::{Image, Args};
use std::env;
use std::fs;
use std::path::Path;
use std::time::Instant;

fn main() -> Result<(), Box<dyn std::error::Error>> {
    let args: Vec<String> = env::args().collect();
    let mut image_path = None;
    let mut lang = "eng".to_string();
    let mut batch = false;
    let mut output = None;

    let mut i = 1;
    while i < args.len() {
        match args[i].as_str() {
            "--lang" | "-l" => { lang = args[i+1].clone(); i += 2; }
            "--batch" | "-b" => { batch = true; i += 1; }
            "--output" | "-o" => { output = Some(args[i+1].clone()); i += 2; }
            _ => {
                if image_path.is_none() {
                    image_path = Some(args[i].clone());
                }
                i += 1;
            }
        }
    }

    if image_path.is_none() {
        println!("Usage: cargo run -- <image> [--lang eng] [--batch]");
        std::process::exit(1);
    }

    println!("\x1b[36m✍️ OCR Handwritten Reader (Rust)\x1b[0m");

    let path = image_path.unwrap();

    if batch || Path::new(&path).is_dir() {
        process_batch(&lang, &path)?;
    } else {
        process_single(&lang, &path, output.as_deref())?;
    }

    Ok(())
}

fn process_single(lang: &str, image_path: &str, output: Option<&str>) -> Result<(), Box<dyn std::error::Error>> {
    let start = Instant::now();
    let img = Image::from_path(image_path)?;
    let args = Args::default()
        .lang(lang)
        .psm(6)
        .oem(3);

    let text = rusty_tesseract::image_to_string(&img, &args)?;
    let elapsed = start.elapsed().as_secs_f64();

    print_result(&text, elapsed);

    let out_file = output.unwrap_or(&format!("{}.txt", image_path.replace(".", "_")));
    fs::write(out_file, &text)?;
    println!("💾 Сохранено: {}", out_file);

    Ok(())
}

fn process_batch(lang: &str, dir: &str) -> Result<(), Box<dyn std::error::Error>> {
    let exts = [".jpg", ".jpeg", ".png", ".bmp", ".tiff"];
    let entries = fs::read_dir(dir)?;
    let mut files = Vec::new();

    for entry in entries {
        let entry = entry?;
        let path = entry.path();
        if path.is_file() {
            if let Some(ext) = path.extension() {
                let ext_str = format!(".{}", ext.to_str().unwrap_or("").to_lowercase());
                if exts.contains(&ext_str.as_str()) {
                    files.push(path);
                }
            }
        }
    }

    if files.is_empty() {
        println!("❌ Нет поддерживаемых изображений.");
        return Ok(());
    }

    println!("📁 Найдено {} изображений.", files.len());
    let mut results = Vec::new();

    for (i, file) in files.iter().enumerate() {
        println!("\n[{}/{}] Обработка: {}", i+1, files.len(), file.file_name().unwrap().to_str().unwrap());
        let img = Image::from_path(file.to_str().unwrap())?;
        let args = Args::default().lang(lang).psm(6).oem(3);
        let text = rusty_tesseract::image_to_string(&img, &args)?;
        let out_file = format!("{}.txt", file.file_stem().unwrap().to_str().unwrap());
        fs::write(&out_file, &text)?;
        println!("💾 Сохранено: {}", out_file);
        results.push(serde_json::json!({ "file": file.file_name().unwrap(), "text": text }));
    }

    let json = serde_json::to_string_pretty(&results)?;
    fs::write("ocr_results.json", json)?;
    println!("\n💾 Сохранено JSON: ocr_results.json");

    Ok(())
}

fn print_result(text: &str, elapsed: f64) {
    println!("\n\x1b[32mРезультат:\x1b[0m");
    println!("{}", "─".repeat(50));
    println!("{}", text);
    println!("{}", "─".repeat(50));

    let words = text.split_whitespace().count();
    let chars = text.len();
    println!("\x1b[36m📊 Статистика:\x1b[0m");
    println!("  Слов: {}", words);
    println!("  Символов: {}", chars);
    println!("  Время: {:.2} сек", elapsed);
}
