#!/bin/bash

TARGET="main"
INCLUDE_UNCOMMITTED=false

while [[ "$#" -gt 0 ]]; do
    case $1 in
        -a|--all) INCLUDE_UNCOMMITTED=true ;
        *) TARGET="$1" ;
    esac
    shift
done

# IF CI Mode then use CI merge request 
if [ -n "$CI_MERGE_REQUEST_TARGET_BRANCH_NAME" ]; then
    TARGET="origin/$CI_MERGE_REQUEST_TARGET_BRANCH_NAME"
fi

echo "🔍 Porównywanie z: $TARGET"
if [ "$INCLUDE_UNCOMMITTED" = true ]; then
    echo "📝 Uwzględniam również niescommitowane zmiany."

    FILES=$(git diff --name-only --diff-filter=ACMR "$TARGET" | grep '\.php$' | tr '\n' ' ')
else
    echo "📦 Sprawdzam tylko zmiany w commitach."

    FILES=$(git diff --name-only --diff-filter=ACMR "$TARGET" HEAD | grep '\.php$' | tr '\n' ' ')
fi

if [ -n "$FILES" ]; then
    echo "🚀 Naprawiam: $FILES"
    vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --diff --path-mode=intersection -- $FILES
else
    echo "✅ Brak plików do sprawdzenia."
fi
