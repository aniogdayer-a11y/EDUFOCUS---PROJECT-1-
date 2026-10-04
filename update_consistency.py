import os
import re

directory = r"C:\xampp\htdocs\midterm_project_Aniog\resources\views"
files = [f for f in os.listdir(directory) if f.endswith(".blade.php")]

for file in files:
    filepath = os.path.join(directory, file)
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Generic button replacements based on what was generated previously
    content = re.sub(r'class="bg-black text-white [^"]*hover:bg-white[^"]*"', 'class="pixel-btn"', content)
    content = re.sub(r'class="bg-black text-white [^"]*hover:bg-gray-[89]00[^"]*"', 'class="pixel-btn"', content)
    
    # Specific for welcome secondary buttons
    content = content.replace('class="bg-transparent text-black px-10 py-5 text-sm font-bold uppercase tracking-widest border border-black hover:bg-gray-100 transition-all duration-300"', 'class="pixel-btn pixel-btn-secondary"')
    content = content.replace('class="bg-white text-black text-center px-8 py-4 text-sm font-bold uppercase tracking-widest border border-gray-300 hover:border-black transition-all duration-300"', 'class="pixel-btn pixel-btn-secondary"')

    # Logo styling for consistency (adding pixel-font class)
    content = content.replace('text-2xl font-bold text-black">\n                    <a href="/">EduFocus</a>', 'text-2xl font-bold text-black pixel-font">\n                    <a href="/">EduFocus</a>')
    
    # Dashboard Start focus
    content = content.replace('class="bg-white text-black px-6 py-3 rounded-lg font-bold w-full hover:bg-gray-100 transition shadow"', 'class="pixel-btn pixel-btn-secondary w-full"')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Consistency update complete!")
