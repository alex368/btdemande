#!/bin/bash
# ==========================================================================
# VALIDATION SCRIPT - Vérifier que tous les fixes ont été appliqués
# ==========================================================================
# Usage: docker-compose exec php bash docker/scripts/validate-fix.sh
# ==========================================================================

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0
WARNINGS=0

# ==========================================================================
# HELPER FUNCTIONS
# ==========================================================================

pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
    ((PASSED++))
}

fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    ((FAILED++))
}

warn() {
    echo -e "${YELLOW}⚠ WARN${NC}: $1"
    ((WARNINGS++))
}

info() {
    echo -e "${BLUE}ℹ INFO${NC}: $1"
}

# ==========================================================================
# TESTS
# ==========================================================================

echo ""
echo "=========================================="
echo "Docker Configuration Validation"
echo "=========================================="
echo ""

# ------------------------------------------
# 1. Check Directories Exist
# ------------------------------------------
echo "${BLUE}[1] Checking directories...${NC}"

if [ -d "/var/www/html/var" ]; then
    pass "Directory /var/www/html/var exists"
else
    fail "Directory /var/www/html/var missing"
fi

if [ -d "/var/www/html/var/cache" ]; then
    pass "Directory /var/www/html/var/cache exists"
else
    fail "Directory /var/www/html/var/cache missing"
fi

if [ -d "/var/www/html/var/log" ]; then
    pass "Directory /var/www/html/var/log exists"
else
    fail "Directory /var/www/html/var/log missing"
fi

if [ -d "/var/www/html/var/tmp" ]; then
    pass "Directory /var/www/html/var/tmp exists"
else
    fail "Directory /var/www/html/var/tmp missing"
fi

if [ -d "/var/www/html/public/uploads" ]; then
    pass "Directory /var/www/html/public/uploads exists"
else
    fail "Directory /var/www/html/public/uploads missing"
fi

echo ""

# ------------------------------------------
# 2. Check Permissions
# ------------------------------------------
echo "${BLUE}[2] Checking permissions...${NC}"

# var/ should be writable
if [ -w "/var/www/html/var" ]; then
    pass "Directory /var/www/html/var is writable"
else
    fail "Directory /var/www/html/var is NOT writable"
fi

# var/cache should be writable
if [ -w "/var/www/html/var/cache" ]; then
    pass "Directory /var/www/html/var/cache is writable"
else
    fail "Directory /var/www/html/var/cache is NOT writable"
fi

# var/log should be writable
if [ -w "/var/www/html/var/log" ]; then
    pass "Directory /var/www/html/var/log is writable"
else
    fail "Directory /var/www/html/var/log is NOT writable"
fi

# var/tmp should be writable
if [ -w "/var/www/html/var/tmp" ]; then
    pass "Directory /var/www/html/var/tmp is writable"
else
    fail "Directory /var/www/html/var/tmp is NOT writable"
fi

# public/uploads should be writable
if [ -w "/var/www/html/public/uploads" ]; then
    pass "Directory /var/www/html/public/uploads is writable"
else
    fail "Directory /var/www/html/public/uploads is NOT writable"
fi

echo ""

# ------------------------------------------
# 3. Check Cache is Compiled
# ------------------------------------------
echo "${BLUE}[3] Checking Symfony cache...${NC}"

if [ -d "/var/www/html/var/cache/prod" ]; then
    pass "Cache directory /var/www/html/var/cache/prod exists"
    
    # Check if cache files exist
    CACHE_FILES=$(find /var/www/html/var/cache/prod -type f | wc -l)
    if [ "$CACHE_FILES" -gt 0 ]; then
        pass "Cache is populated ($CACHE_FILES files)"
    else
        fail "Cache directory is empty"
    fi
else
    fail "Cache directory /var/www/html/var/cache/prod is MISSING"
fi

echo ""

# ------------------------------------------
# 4. Check PHP Extensions
# ------------------------------------------
echo "${BLUE}[4] Checking PHP extensions...${NC}"

check_extension() {
    if php -m | grep -q "^$1$"; then
        pass "Extension $1 is loaded"
    else
        fail "Extension $1 is NOT loaded"
    fi
}

check_extension "intl"
check_extension "pdo_mysql"
check_extension "zip"
check_extension "gd"
check_extension "opcache"

echo ""

# ------------------------------------------
# 5. Check Database Connection
# ------------------------------------------
echo "${BLUE}[5] Checking database connection...${NC}"

if php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; then
    pass "Database connection successful"
else
    warn "Database connection failed (check DB service status)"
fi

echo ""

# ------------------------------------------
# 6. Check Environment Variables
# ------------------------------------------
echo "${BLUE}[6] Checking environment variables...${NC}"

if [ ! -z "$APP_ENV" ]; then
    pass "APP_ENV is set: $APP_ENV"
else
    warn "APP_ENV is not set"
fi

if [ ! -z "$DATABASE_URL" ]; then
    pass "DATABASE_URL is configured"
else
    fail "DATABASE_URL is not set"
fi

if [ ! -z "$OLLAMA_BASE_URL" ]; then
    pass "OLLAMA_BASE_URL is set: $OLLAMA_BASE_URL"
else
    warn "OLLAMA_BASE_URL is not set"
fi

if [ ! -z "$OLLAMA_CHAT_MODEL" ]; then
    pass "OLLAMA_CHAT_MODEL is set: $OLLAMA_CHAT_MODEL"
else
    warn "OLLAMA_CHAT_MODEL is not set"
fi

echo ""

# ------------------------------------------
# 7. Check Tesseract OCR
# ------------------------------------------
echo "${BLUE}[7] Checking Tesseract OCR...${NC}"

if command -v tesseract &> /dev/null; then
    pass "Tesseract is installed"
    
    TESSERACT_VERSION=$(tesseract --version 2>&1 | head -1)
    info "Tesseract version: $TESSERACT_VERSION"
else
    fail "Tesseract is NOT installed"
fi

if [ -f "/usr/share/tessdata/fra.traineddata" ]; then
    pass "French language pack (fra) is installed"
else
    warn "French language pack (fra) is missing"
fi

if [ -f "/usr/share/tessdata/eng.traineddata" ]; then
    pass "English language pack (eng) is installed"
else
    warn "English language pack (eng) is missing"
fi

echo ""

# ------------------------------------------
# 8. Check User Permissions
# ------------------------------------------
echo "${BLUE}[8] Checking user/group setup...${NC}"

CURRENT_USER=$(whoami)
if [ "$CURRENT_USER" = "app" ]; then
    pass "Running as user 'app'"
else
    warn "Running as user '$CURRENT_USER' (expected 'app')"
fi

echo ""

# ------------------------------------------
# 9. Check Logs
# ------------------------------------------
echo "${BLUE}[9] Checking logs...${NC}"

if [ -f "/var/www/html/var/log/prod.log" ]; then
    pass "Log file /var/www/html/var/log/prod.log exists"
    
    if [ -w "/var/www/html/var/log/prod.log" ]; then
        pass "Log file is writable"
    else
        fail "Log file is NOT writable"
    fi
else
    info "Log file doesn't exist yet (will be created on first error)"
fi

echo ""

# ------------------------------------------
# 10. Check Application Status
# ------------------------------------------
echo "${BLUE}[10] Checking application status...${NC}"

if [ -x "/var/www/html/bin/console" ]; then
    pass "Symfony console is executable"
    
    if php bin/console about > /dev/null 2>&1; then
        pass "Symfony application is functional"
    else
        fail "Symfony application status check failed"
    fi
else
    fail "Symfony console not found"
fi

echo ""

# ==========================================================================
# SUMMARY
# ==========================================================================

echo "=========================================="
echo "Summary"
echo "=========================================="
echo ""
echo -e "Tests Passed:    ${GREEN}$PASSED${NC}"
echo -e "Tests Failed:    ${RED}$FAILED${NC}"
echo -e "Tests Warnings:  ${YELLOW}$WARNINGS${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ ALL CHECKS PASSED - Configuration is correct!${NC}"
    exit 0
else
    echo -e "${RED}✗ SOME CHECKS FAILED - Please review above${NC}"
    exit 1
fi
