#!/bin/bash
#
# Security Scan Script for Mobility Trailblazers Plugin
# Runs PHP CodeSniffer with WordPress Security Standards
#

echo "======================================"
echo "Mobility Trailblazers Security Scanner"
echo "======================================"
echo ""

# Check if phpcs is installed
if ! command -v phpcs &> /dev/null
then
    echo "ERROR: PHP CodeSniffer (phpcs) is not installed."
    echo "Please install it using: composer global require squizlabs/php_codesniffer"
    exit 1
fi

# Check if WordPress Coding Standards are installed
if ! phpcs -i | grep -q "WordPress"
then
    echo "WARNING: WordPress Coding Standards not installed."
    echo "Installing WordPress Coding Standards..."
    composer global require wp-coding-standards/wpcs
    phpcs --config-set installed_paths ~/.composer/vendor/wp-coding-standards/wpcs
fi

# Set the plugin directory
PLUGIN_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Scanning plugin directory: $PLUGIN_DIR"
echo ""

# Run security-focused scan
echo "1. Running Security Scan..."
echo "---------------------------"
phpcs --standard=WordPress-Security \
      --severity=5 \
      --extensions=php \
      --report=full \
      --report-width=120 \
      -p \
      "$PLUGIN_DIR/includes" \
      "$PLUGIN_DIR/templates" \
      "$PLUGIN_DIR/mobility-trailblazers.php"

# Run nonce verification check
echo ""
echo "2. Checking Nonce Verification..."
echo "----------------------------------"
phpcs --standard=WordPress-Security \
      --sniffs=WordPress.Security.NonceVerification \
      --extensions=php \
      --report=summary \
      "$PLUGIN_DIR"

# Run escape output check
echo ""
echo "3. Checking Output Escaping..."
echo "-------------------------------"
phpcs --standard=WordPress-Security \
      --sniffs=WordPress.Security.EscapeOutput \
      --extensions=php \
      --report=summary \
      "$PLUGIN_DIR"

# Run SQL injection check
echo ""
echo "4. Checking SQL Queries..."
echo "---------------------------"
phpcs --standard=WordPress-Security \
      --sniffs=WordPress.DB.PreparedSQL \
      --extensions=php \
      --report=summary \
      "$PLUGIN_DIR"

# Generate detailed report
echo ""
echo "5. Generating Detailed Report..."
echo "---------------------------------"
phpcs --standard=WordPress-Security \
      --severity=1 \
      --extensions=php \
      --report=json \
      --report-file="$PLUGIN_DIR/security-report.json" \
      "$PLUGIN_DIR"

echo ""
echo "Security scan complete!"
echo "Detailed report saved to: security-report.json"
echo ""
echo "======================================"
