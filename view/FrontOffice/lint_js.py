import re

with open('c:/xampp/htdocs/user_web1_v2/view/FrontOffice/login.html', 'r', encoding='utf-8') as f:
    content = f.read()

scripts = re.findall(r'<script.*?>([\s\S]*?)</script>', content)

for i, script in enumerate(scripts):
    stack = []
    lines = script.split('\n')
    for line_idx, line in enumerate(lines):
        for char_idx, char in enumerate(line):
            if char in '{[(':
                stack.append((char, line_idx+1))
            elif char in '}])':
                if not stack:
                    print(f"Script {i}: Extra '{char}' at line {line_idx+1}")
                else:
                    top_char, _ = stack.pop()
                    # simplistic check, ignoring mismatched types for now
    if stack:
        print(f"Script {i}: Unclosed '{stack[-1][0]}' opened at line {stack[-1][1]}")
    else:
        print(f"Script {i}: Balanced")
