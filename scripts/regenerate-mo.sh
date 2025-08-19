#!/bin/bash
# Regenerate German .mo file from .po file
# This ensures all translations are properly compiled

cd "E:\OneDrive\CoWorkSpace\Tech Stack\Platform\plugin\mobility-trailblazers\languages"

echo "Regenerating German language files..."

# Use msgfmt if available (requires gettext tools)
if command -v msgfmt &> /dev/null; then
    msgfmt -o mobility-trailblazers-de_DE.mo mobility-trailblazers-de_DE.po
    echo "✅ Successfully regenerated mobility-trailblazers-de_DE.mo"
else
    echo "⚠️  msgfmt not found. Please install gettext tools or use Poedit to compile the .po file"
    echo "Alternative: Use online tool at https://po2mo.net/"
fi

echo "Done!"