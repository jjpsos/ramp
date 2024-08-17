<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'jjpsos' );

/** Database password */
define( 'DB_PASSWORD', '2cat87mt' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' ); 
 

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'kY#9gYB-P@VMeR0n;1;uaK8SC]oVlGcme]L9JhgZh1Tfd253a7<y1NTweRFpS7J8' );
define( 'SECURE_AUTH_KEY',  '5bOuUTQ]{-eK?n9K,.Y&Y}euWOOWSZm_l3>^Rwyq=i3$4:l_1g9+SwK)0U4-^bgq' );
define( 'LOGGED_IN_KEY',    '$O4g;FD/w}z752up<vZ%|hYD5.5So+g|THo),O`Pt6c*)-SPH-=oPSrQ,T+[THKD' );
define( 'NONCE_KEY',        '4=UClzsc40wKDd%|k1RyOp|hRY1c 02]|X>s>) VpPSj5f,G5hgv*W,CpIDqEF{-' );
define( 'AUTH_SALT',        '(ucRlh%g|U)Wd9[]@xP#Cv{XU&o.SbQ1~2n><Cw)*L*)6_-{w[$ e~R*1k5X=i@X' );
define( 'SECURE_AUTH_SALT', '$U2$D`6FU.72=0a8JoU8ULL3n4_B yP[kFI(qOy!61%`xehHWqAs=Y-Kwf ~6*m`' );
define( 'LOGGED_IN_SALT',   '[Zw@{c#Mr)dP`e>LwoB4,f$XNWa8Y2tV3uSZV}VDf40L jnAIOLn^t|_-hF$4Mb5' );
define( 'NONCE_SALT',       '//gnl>I=IjY}u$T*8G6@3$Y%B{zzFA.Om1muT].=]ud!,|C)qIjKSC{wtF,]j^ZC' );

/**#@-*/

/**
 * WordPress database table prefix.
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

/* Add any custom values between this line and the "stop editing" line. */

define( 'WP_AUTO_UPDATE_TRANSLATION', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';