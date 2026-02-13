#!/bin/bash
set -e

# ======================================================
# Xdebug – code coverage
# ======================================================
export XDEBUG_MODE=coverage
export XDEBUG_START_WITH_REQUEST=yes

[ -f /workspace/.env.ci ] && source /workspace/.env.ci
: "${CI_PROJECT_DIR:=$(pwd)}"

echo "[=] Running tests in $CI_PROJECT_DIR"


mkdir -p "$CI_PROJECT_DIR/reports"

php -m | grep xdebug || { echo "❌ Xdebug not loaded"; exit 1; }

# ======================================================
# PHPUnit
# ======================================================
vendor/bin/phpunit \
    --configuration=phpunit.ci.xml.dist \
    --coverage-text \
    --coverage-cobertura="$CI_PROJECT_DIR/reports/coverage.cobertura.xml" \
    --log-junit "$CI_PROJECT_DIR/reports/report.xml"

echo "---"
echo "[=] Zawartość katalogu raportów po testach:"
ls -la "$CI_PROJECT_DIR/reports"
