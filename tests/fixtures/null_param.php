<?php
/**
 * Filter the username before we do anything else.
 *
 * @param null   $pre      The pre-existing value.
 * @param string $username The username.
 */
$pre = apply_filters( 'activitypub_pre_get_by_username', null, $username );
