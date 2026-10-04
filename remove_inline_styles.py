import os

directory = r"C:\xampp\htdocs\midterm_project_Aniog\resources\views"
files = ["login.blade.php", "register.blade.php"]

for file in files:
    filepath = os.path.join(directory, file)
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Find and remove everything between <style> and </style>
    import re
    content = re.sub(r'<style>.*?</style>', '', content, flags=re.DOTALL)
    
    # Remove google fonts link as it's now in app.css
    content = content.replace('<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">', '')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Inline styles removed!")
