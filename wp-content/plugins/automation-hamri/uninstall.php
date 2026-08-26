<?php
/**
 * Uninstall routine for Automation Hamri (WP Automator Pro).
 *
 * Runs ONLY when the plugin is deleted from the WordPress admin
 * (Plugins → Delete) — never on deactivate.
 *
 * IMPORTANT — DATA IS PRESERVED ON PURPOSE.
 * This install is maintained as a series of separate plugin folders (each
 * version installs alongside the previous one). Deleting an old version to
 * declutter must NOT wipe the shared Distribution Hub table or the settings,
 * or every version switch would erase the Hub listing and all ad codes.
 *
 * Therefore this uninstall keeps:
 *   • the wpap_generated_posts table (the Distribution Hub listing),
 *   • all settings/ad/automation/UTM/IndexNow options.
 * It clears only disposable license transients. The recurring automation cron is
 * intentionally left to the deactivation hook (see wpap_deactivate in the main
 * file) and deliberately NOT touched here (see the note below).
 *
 * Published posts and their scheduled publish events are — and always were —
 * left completely intact. Deleting the plugin never touches your content.
 *
 * If you ever want a TRUE clean wipe (drop the table + delete all options),
 * do it deliberately from a database tool — it is intentionally NOT automatic.
 */

/* Guard: only ever run inside WordPress's uninstall context. */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/* NOTE: we deliberately do NOT clear the wpap_automation_cron hook here. Because
   this plugin is distributed as multiple parallel version folders, deleting an
   OLD folder would otherwise unschedule the cron that the ACTIVE version relies
   on. The deactivation hook already clears the cron for the version actually
   being turned off; a dangling hook after a true full-uninstall is a harmless
   no-op (it fires with no listener). */

/* Disposable license transients (safe to drop; they auto-regenerate if needed). */
delete_transient( 'wpap_license_cache' );
delete_transient( 'wpap_revoke_notice' );

/* NOTE: DROP TABLE and delete_option(...) calls are intentionally omitted so
   that deleting one installed version never erases the shared Hub or settings. */
