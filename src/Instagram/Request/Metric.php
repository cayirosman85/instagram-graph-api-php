<?php


namespace Instagram\Request;

/**
 * Metric
 *
 * Functionality and defines for the Instagram Graph API metric query parameter.
 *
 * @package     instagram-graph-api-php-sdk
 * @author      Justin Stolpe
 * @link        https://github.com/jstolpe/instagram-graph-api-php-sdk
 * @license     https://opensource.org/licenses/MIT
 * @version     1.0
 */
class Metric {
    const AUDIENCE_CITY = 'audience_city';
    const AUDIENCE_COUNTRY = 'audience_country';
    const AUDIENCE_GENDER_AGE = 'audience_gender_age';
    const AUDIENCE_LOCALE = 'audience_locale';
    const CAROUSEL_ALBUM_ENGAGEMENT = 'carousel_album_engagement';
    const CAROUSEL_ALBUM_IMPRESSIONS = 'carousel_album_impressions';
    const CAROUSEL_ALBUM_REACH = 'carousel_album_reach';
    const CAROUSEL_ALBUM_SAVED = 'carousel_album_saved';
    const CAROUSEL_ALBUM_VIDEO_VIEWS = 'carousel_album_video_views';
    const EMAIL_CONTACTS = 'email_contacts';
    const ENGAGEMENT = 'engagement';
    const EXITS = 'exits';
    const FOLLOWER_COUNT = 'follower_count';
    const GET_DIRECTIONS_LINK = 'get_directions_clicks';
    const IMPRESSIONS = 'impressions';
    const MEDIA_TYPE_CAROUSEL_ALBUM = 'carousel_album';
    const MEDIA_TYPE_IMAGE = 'image';
    const MEDIA_TYPE_STORY = 'story';
    const MEDIA_TYPE_VIDEO = 'video';
    const ONLINE_FOLLOWERS = 'online_followers';
    const PHONE_CALL_CLICKS = 'phone_call_clicks';
    const PROFILE_VIEWS = 'profile_views';
    const REACH = 'reach';
    const REPLIES = 'replies';
    const SAVED = 'saved';
    const TAPS_BACK = 'taps_back';
    const TAPS_FORWARD = 'taps_forward';
    const TEXT_MESSAGE_CLICKS = 'text_message_clicks';
    const VIDEO_VIEWS = 'video_views';
    const WEBSITE_CLICKS = 'website_clicks';
    const LIKES = 'likes';
    const COMMENTS = 'comments';
    const SHARES = 'shares';
    // Newly added metrics from the Instagram Graph API error message
    const PLAYS = 'plays';
    const TOTAL_INTERACTIONS = 'total_interactions';
    const FOLLOWS = 'follows';
    const PROFILE_VISITS = 'profile_visits'; // Adding explicitly, even though PROFILE_VIEWS exists
    const PROFILE_ACTIVITY = 'profile_activity';
    const NAVIGATION = 'navigation';
    const IG_REELS_VIDEO_VIEW_TOTAL_TIME = 'ig_reels_video_view_total_time';
    const IG_REELS_AVG_WATCH_TIME = 'ig_reels_avg_watch_time';
    const CLIPS_REPLAYS_COUNT = 'clips_replays_count';
    const IG_REELS_AGGREGATED_ALL_PLAYS_COUNT = 'ig_reels_aggregated_all_plays_count';
    const VIEWS = 'views';
}

?>