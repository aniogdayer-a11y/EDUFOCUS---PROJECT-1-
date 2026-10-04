import os
import re

directory = r"C:\xampp\htdocs\midterm_project_Aniog\resources\views"
blade_files = [f for f in os.listdir(directory) if f.endswith(".blade.php")]

for file in blade_files:
    filepath = os.path.join(directory, file)
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace primary color bg/text/border with black/white equivalents
    content = re.sub(r'bg-(indigo|blue|red|green|yellow|purple)-600', 'bg-black', content)
    content = re.sub(r'bg-(indigo|blue|red|green|yellow|purple)-700', 'bg-gray-900', content)
    content = re.sub(r'text-(indigo|blue|red|green|yellow|purple)-600', 'text-black', content)
    content = re.sub(r'border-(indigo|blue|red|green|yellow|purple)-600', 'border-black', content)
    
    # Replace lighter backgrounds
    content = re.sub(r'bg-(indigo|blue|red|green|yellow|purple)-50', 'bg-gray-50', content)
    content = re.sub(r'bg-(indigo|blue|red|green|yellow|purple)-100', 'bg-gray-100', content)
    
    # Replace lighter borders
    content = re.sub(r'border-(indigo|blue|red|green|yellow|purple)-100', 'border-gray-200', content)
    content = re.sub(r'border-(indigo|blue|red|green|yellow|purple)-200', 'border-gray-300', content)

    # Replace text colors
    content = re.sub(r'text-(indigo|blue|red|green|yellow|purple)-800', 'text-black', content)
    content = re.sub(r'text-(indigo|blue|red|green|yellow|purple)-900', 'text-black', content)
    content = re.sub(r'text-(indigo|blue|red|green|yellow|purple)-500', 'text-black', content)
    
    # Replace dot colors
    content = re.sub(r'bg-(indigo|blue|red|green|yellow|purple)-500', 'bg-black', content)

    # Some specific replacements
    content = content.replace('text-indigo-600', 'text-black')
    content = content.replace('text-yellow-500', 'text-black')
    content = content.replace('text-green-500', 'text-black')
    content = content.replace('text-blue-500', 'text-black')

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Color update complete!")
