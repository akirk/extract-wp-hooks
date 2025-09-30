<?php
/**
 * ActivityPub inbox action for specific activity types.
 *
 * @param array              $data     The data array.
 * @param int|null           $user_id  The user ID.
 * @param Activity|\WP_Error $activity The Activity object.
 */
do_action( 'activitypub_inbox_' . $type, $data, $user_id, $activity );
