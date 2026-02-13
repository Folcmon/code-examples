#!/bin/bash


ANALYZE_ALL=false
BASE=""
DRY_RUN=true
PHPSTAN_OPTS=""

while [[ $# -gt 0 ]]; do
  key="$1"
  case $key in
    -b|--base)
      BASE="origin/$2"
      shift 2
      ;;
    -a|--all)
      ANALYZE_ALL=true
      shift
      ;;
    --ci-mode)
      DRY_RUN=false
      shift
      ;;
    *)
      PHPSTAN_OPTS="$PHPSTAN_OPTS $1"
      shift
      ;;
  esac
done

cd "$(git rev-parse --show-toplevel)" || exit


if [ -z "$BASE" ]; then
    echo "[i] Base branch not specified. Defaulting to 'origin/main'."
    BASE="origin/main"
fi

BRANCH_NAME=${BASE#origin/}
echo "[i] Fetching base branch '$BRANCH_NAME' from origin..."

REMOTE_URL=$(git remote get-url origin 2>/dev/null || true)
if [[ -n "$CI_JOB_TOKEN" && "$REMOTE_URL" =~ ^https?:// ]]; then
    echo "[i] CI environment detected and origin is HTTPS — using CI job token for authenticated fetch."
    AUTH_REMOTE_URL=$(echo "$REMOTE_URL" | sed -E "s#https?://#https://gitlab-ci-token:${CI_JOB_TOKEN}@#")
    git fetch "$AUTH_REMOTE_URL" "$BRANCH_NAME"
else
    git fetch origin "$BRANCH_NAME"
fi


if [ "$ANALYZE_ALL" = true ]; then
    echo "[=] Including uncommitted changes (-a)"
    FILES=$(git diff --name-only --diff-filter=ACMR "$BASE" | grep '\.php$')
else
    FILES=$(git diff --name-only --diff-filter=ACMR "$BASE" HEAD | grep '\.php$')
fi


if [ -z "$FILES" ]; then
    echo "No PHP files changed. Skipping."
    exit 0
fi

echo "[=] Analyzing changed files..."

echo "$FILES" | xargs vendor/bin/phpstan analyze --no-progress "$PHPSTAN_OPTS"
RESULT=$?

if [ "$DRY_RUN" = true ]; then
    echo "[i] Dry-run mode is active. Ignoring PHPStan exit code."
    exit 0
fi

exit $RESULT
