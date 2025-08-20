#!/bin/bash
# =====================================================
# CLEANUP SCRIPT - Remove debugging and sensitive files
# Date: 2025-08-20
# =====================================================

echo "🧹 Starting repository cleanup..."
echo ""

# Base directory
BASE_DIR="E:/OneDrive/CoWorkSpace/Tech Stack/Platform/plugin/mobility-trailblazers"
cd "$BASE_DIR"

# Files to remove (with sensitive credentials or debug code)
FILES_TO_REMOVE=(
    # Tools directory - all debug/fix tools
    "tools/diagnose-usernames.php"
    "tools/execute-fix.php"
    "tools/fix-jury-usernames.php"
    
    # SQL directory - contains database credentials
    "sql/PRODUCTION_FIX_NOW.sql"
    
    # Scripts with credentials
    "scripts/fix-production-usernames.sh"
    
    # Doc files with SQL credentials
    "doc/fix-jury-usernames-2025-08-20.sql"
    "doc/fix-jury-usernames-comprehensive-2025-08-20.sql"
    
    # Session summary (might contain sensitive info)
    "doc/USERNAME_FIX_GUIDE.md"
)

# Remove each file
echo "📁 Removing sensitive files..."
for file in "${FILES_TO_REMOVE[@]}"; do
    if [ -f "$file" ]; then
        rm -f "$file"
        echo "  ✓ Removed: $file"
    else
        echo "  ⚠ Not found: $file"
    fi
done

# Remove the entire SQL directory if it exists
if [ -d "sql" ]; then
    rm -rf sql
    echo "  ✓ Removed: sql directory"
fi

# Clean up empty directories
echo ""
echo "🗑️ Cleaning up empty directories..."
if [ -d "tools" ] && [ -z "$(ls -A tools)" ]; then
    rmdir tools
    echo "  ✓ Removed empty tools directory"
fi

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "📋 Kept important documentation:"
echo "  - doc/USERNAME_FIX_COMPLETED.md (clean summary without credentials)"
echo "  - doc/CHANGELOG.md (project history)"
echo "  - Other development guides"
echo ""
echo "⚠️ Remember to:"
echo "  1. git add -A"
echo "  2. git commit -m 'chore: remove debugging files and sensitive credentials'"
echo "  3. git push"
