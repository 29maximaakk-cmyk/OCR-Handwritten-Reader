# handwritten_ocr.rb — Ruby версия

require 'rtesseract'
require 'json'
require 'optparse'
require 'time'
require 'find'

class HandwrittenOCR
  def initialize(lang = 'eng', psm = 6, oem = 3)
    @lang = lang
    @psm = psm
    @oem = oem
    @results = []
  end

  def ocr_image(image_path)
    puts "📂 Обработка: #{File.basename(image_path)}"
    start = Time.now
    text = RTesseract.new(
      image_path,
      lang: @lang,
      psm: @psm,
      oem: @oem
    ).to_s.strip
    elapsed = Time.now - start

    print_result(text, elapsed)
    save_text(text, image_path)
    text
  end

  def ocr_batch(dir)
    exts = %w[.jpg .jpeg .png .bmp .tiff .webp]
    files = Dir.entries(dir).select { |f| exts.include?(File.extname(f).downcase) }

    if files.empty?
      puts "❌ Нет поддерживаемых изображений."
      return
    end

    puts "📁 Найдено #{files.size} изображений."
    files.each_with_index do |file, i|
      puts "\n[#{i+1}/#{files.size}] Обработка: #{file}"
      text = ocr_image(File.join(dir, file))
      @results << { file: file, text: text } if text
    end

    save_json('ocr_results.json')
  end

  def print_result(text, elapsed)
    puts "\n\e[32mРезультат:\e[0m"
    puts "─" * 50
    puts text
    puts "─" * 50

    words = text.split(/\s+/).reject(&:empty?).size
    chars = text.size
    puts "\e[36m📊 Статистика:\e[0m"
    puts "  Слов: #{words}"
    puts "  Символов: #{chars}"
    puts "  Время: #{elapsed.round(2)} сек"
  end

  def save_text(text, image_path)
    out_file = File.basename(image_path, '.*') + '.txt'
    File.write(out_file, text)
    puts "💾 Сохранено: #{out_file}"
  end

  def save_json(filename)
    File.write(filename, JSON.pretty_generate(@results))
    puts "💾 Сохранено JSON: #{filename}"
  end
end

def main
  options = {}
  OptionParser.new do |opts|
    opts.banner = "Usage: ruby handwritten_ocr.rb <image> [--lang eng] [--batch]"
    opts.on("--lang LANG", "Язык OCR") { |v| options[:lang] = v }
    opts.on("--batch", "Пакетная обработка") { options[:batch] = true }
    opts.on("--output FILE", "Файл для сохранения") { |v| options[:output] = v }
  end.parse!

  input = ARGV[0]
  unless input
    puts "Usage: ruby handwritten_ocr.rb <image> [--lang eng] [--batch]"
    exit 1
  end

  puts "\e[36m✍️ OCR Handwritten Reader (Ruby)\e[0m"

  lang = options[:lang] || 'eng'
  ocr = HandwrittenOCR.new(lang)

  if options[:batch] || File.directory?(input)
    ocr.ocr_batch(input)
  else
    unless File.exist?(input)
      puts "\e[31m❌ Файл не найден: #{input}\e[0m"
      exit 1
    end
    ocr.ocr_image(input)
  end
end

main if __FILE__ == $0
