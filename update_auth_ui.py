import os

directory = r"C:\xampp\htdocs\midterm_project_Aniog\resources\views"
files_to_update = ["dashboard.blade.php", "lessons.blade.php", "profile.blade.php", "welcome.blade.php", "about.blade.php"]

logout_form = """
                    @auth
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="hover:text-gray-900 font-semibold ml-4">Logout</button>
                    </form>
                    @else
                    <a href="/login" class="hover:text-gray-900 font-semibold ml-4">Login</a>
                    @endauth
"""

for file in files_to_update:
    filepath = os.path.join(directory, file)
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Update hardcoded "Student" and "Dayer Aniog"
    content = content.replace("Hello, Student! \ud83d\udc4b", "Hello, {{ auth()->user()->name ?? 'Student' }}! \ud83d\udc4b")
    content = content.replace("Hello, Student!", "Hello, {{ auth()->user()->name ?? 'Student' }}!")
    content = content.replace("<span>Student</span>", "<span>{{ auth()->user()->name ?? 'Student' }}</span>")
    content = content.replace(">Dayer Aniog<", ">{{ auth()->user()->name ?? 'Dayer Aniog' }}<")
    content = content.replace("student@example.com", "{{ auth()->user()->email ?? 'student@example.com' }}")
    
    # Simple replace to inject a logout button near the profile area
    if file in ["dashboard.blade.php", "lessons.blade.php", "profile.blade.php"]:
        # Find the closing div of the space-x-2 for the profile avatar and insert logout
        content = content.replace("<span>{{ auth()->user()->name ?? 'Student' }}</span>\n                    </div>", 
                                  "<span>{{ auth()->user()->name ?? 'Student' }}</span>\n                    </div>" + logout_form)
    
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

print("Auth UI update complete!")
