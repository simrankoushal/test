<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'test' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'R^<xeXVobZ|vKTiH/$P5(l5}>&X*,+@3:k?Tf&DbbN7}G[47y8|&}KW+q?{JIH=p' );
define( 'SECURE_AUTH_KEY',  '&By#pj!fLDxTuu[e|$z.geyempT=9u!}D/Q0_vElg.z#N;%sioNk35gc`N}:APr-' );
define( 'LOGGED_IN_KEY',    '<?$O>71p!`:r*f$w[&;uH ;dTW@)!d,6LiHFSq:i|/CrJwQ@:ofg`F8rW4wi+HSY' );
define( 'NONCE_KEY',        'Hz|P;-3gnn$=vyn1-rc`[2A+*fp K^R~V$M|Nc{y^&Lmr(he)@8W-,Z[f7hz`dqk' );
define( 'AUTH_SALT',        '6y0A_SvwqI.N6rJK_d^@+8oiXCDq|Vk9qQ<S43/;{f@V$;5~nSnVp)Kge*0 OV2z' );
define( 'SECURE_AUTH_SALT', 'w>u]?Ne*y>R&: `4nn>cO[$y_N@a8mLa[y:{:)H8aE1M5@qJg7`,^6Eg>-O/|;y<' );
define( 'LOGGED_IN_SALT',   '7|y%me6XK2Urb;ZD?koFw_mq^5]ZI}2<i4I6U>@zCh5V]PzJX@i~~U/{i.Oct &@' );
define( 'NONCE_SALT',       '`|By@oP+nn}i3Lpl8{v=RAg/Z=7<]7m8+c1Z2J(-zrs-sUr01$xZhk(:/`n?,)+S' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
