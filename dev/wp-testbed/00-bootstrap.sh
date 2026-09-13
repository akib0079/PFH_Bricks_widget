#!/bin/bash
# Stand up a real WordPress + WooCommerce install to test the archive engine.
#
# There is no MySQL on the dev machine, so this runs on SQLite via the official
# sqlite-database-integration drop-in and PHP's built-in server. Bricks is a
# paid theme and cannot be downloaded — mu-bricks-stub.php stands in for the
# base class the elements extend, which is all they need. The filter engine
# itself is plain WordPress and WooCommerce, so it is tested for real.
#
#   bash dev/wp-testbed/00-bootstrap.sh /path/to/workdir
#
set -euo pipefail

ROOT="${1:?usage: 00-bootstrap.sh <workdir>}"
HERE="$(cd "$(dirname "$0")" && pwd)"
PLUGIN="$(cd "$HERE/../../pfh-bricks-widgets" && pwd)"
PORT="${PORT:-8913}"

mkdir -p "$ROOT/dl" "$ROOT/wp"
cd "$ROOT/dl"

[ -s wp.tar.gz ]  || curl -sS -L -o wp.tar.gz  https://wordpress.org/latest.tar.gz
[ -s woo.zip ]    || curl -sS -L -o woo.zip    https://downloads.wordpress.org/plugin/woocommerce.zip
[ -s sqlite.zip ] || curl -sS -L -o sqlite.zip https://github.com/WordPress/sqlite-database-integration/archive/refs/heads/main.zip

if [ ! -f "$ROOT/wp/wp-settings.php" ]; then
	tar -xzf wp.tar.gz -C "$ROOT"
	mv "$ROOT/wordpress"/* "$ROOT/wp/"
	rmdir "$ROOT/wordpress"
fi

cd "$ROOT/wp/wp-content/plugins"
[ -d woocommerce ] || unzip -q "$ROOT/dl/woo.zip"

if [ ! -d sqlite-database-integration ]; then
	unzip -q "$ROOT/dl/sqlite.zip"
	mv sqlite-database-integration-main sqlite-database-integration
fi

sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$ROOT/wp/wp-content/plugins/sqlite-database-integration#" \
	sqlite-database-integration/db.copy > "$ROOT/wp/wp-content/db.php"

rm -rf "$ROOT/wp/wp-content/plugins/pfh-bricks-widgets"
cp -R "$PLUGIN" "$ROOT/wp/wp-content/plugins/"

mkdir -p "$ROOT/wp/wp-content/mu-plugins"
cp "$HERE/mu-bricks-stub.php" "$ROOT/wp/wp-content/mu-plugins/"

cat > "$ROOT/wp/wp-config.php" <<CFG
<?php
define( 'DB_NAME', 'pfhtest' );
define( 'DB_USER', 'unused' );
define( 'DB_PASSWORD', 'unused' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
define( 'WP_HOME', 'http://localhost:$PORT' );
define( 'WP_SITEURL', 'http://localhost:$PORT' );
define( 'AUTH_KEY', 'k1' ); define( 'SECURE_AUTH_KEY', 'k2' );
define( 'LOGGED_IN_KEY', 'k3' ); define( 'NONCE_KEY', 'k4' );
define( 'AUTH_SALT', 's1' ); define( 'SECURE_AUTH_SALT', 's2' );
define( 'LOGGED_IN_SALT', 's3' ); define( 'NONCE_SALT', 's4' );
define( 'WP_DEBUG', true ); define( 'WP_DEBUG_LOG', true ); define( 'WP_DEBUG_DISPLAY', false );
define( 'DISABLE_WP_CRON', true );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
\$table_prefix = 'wp_';
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
require_once ABSPATH . 'wp-settings.php';
CFG

cat > "$ROOT/wp/01-install.php" <<'INS'
<?php
define( 'WP_INSTALLING', true );
require __DIR__ . '/wp-load.php';
require ABSPATH . 'wp-admin/includes/upgrade.php';
if ( is_blog_installed() ) { exit( "already installed\n" ); }
$r = wp_install( 'PFH Shop Test', 'admin', 'admin@pfh.test', true, '', 'pfhtestpass' );
echo "installed, user " . ( $r['user_id'] ?? '?' ) . "\n";
INS

cp "$HERE/02-woocommerce.php" "$HERE/03-demo-products.php" "$HERE/04-test-page.php" "$HERE/test-engine.php" "$ROOT/wp/"

cd "$ROOT/wp"
php 01-install.php
php 02-woocommerce.php    | grep -viE '^(PHP )?(Warning|Notice|Deprecated)' || true
php 03-demo-products.php  | grep -viE '^(PHP )?(Warning|Notice|Deprecated)' || true
php 04-test-page.php      | grep -viE '^(PHP )?(Warning|Notice|Deprecated)' || true

echo
echo "Ready. Run the engine suite:"
echo "  cd $ROOT/wp && php test-engine.php"
echo "Serve it:"
echo "  cd $ROOT/wp && php -S 127.0.0.1:$PORT"
echo "  http://127.0.0.1:$PORT/shop-test/"
