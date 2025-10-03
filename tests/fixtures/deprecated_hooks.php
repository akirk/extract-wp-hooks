<?php

/**
 * Test file for deprecated hooks.
 */

/**
 * Deprecated action hook.
 *
 * @deprecated 7.5.0 Use "activitypub_handled_follow" instead.
 *
 * @param string $actor    The URL of the actor.
 * @param array  $activity The activity data.
 * @param int    $user_id  The user ID.
 */
\do_action_deprecated( 'activitypub_followers_post_follow', array( $activity['actor'], $activity, $user_id ), '7.5.0', 'activitypub_handled_follow' );

/**
 * Deprecated filter hook.
 *
 * @deprecated 7.1.0 Please migrate your Followings to the new internal Following structure.
 *
 * @param array $items The array of following urls.
 * @param object $user The user object.
 */
$items = \apply_filters_deprecated( 'activitypub_rest_following', array( array(), $user ), '7.1.0', 'Please migrate your Followings to the new internal Following structure.' );

/**
 * Deprecated hook without replacement.
 *
 * @deprecated 8.0.0
 *
 * @param string $data Some data.
 */
\do_action_deprecated( 'old_legacy_hook', array( $data ), '8.0.0' );

/**
 * Deprecated hook without version or replacement.
 *
 * @deprecated
 *
 * @param int $id Some ID.
 */
\do_action_deprecated( 'very_old_hook', array( $id ) );