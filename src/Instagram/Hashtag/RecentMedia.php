<?php
namespace Instagram\Hashtag;

use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;

class RecentMedia extends Hashtag {
    const ENDPOINT = 'recent_media';
    protected $userId;

    public function __construct( $config ) {
        parent::__construct( $config );
        $this->userId = $config['user_id'];
    }

    public function getSelf( $params = array() ) {
        $getParams = array(
            'endpoint' => '/' . $this->hashtagId . '/' . self::ENDPOINT,
            'params' => $this->getParams( $params )
        );
        $response = $this->get( $getParams );
        return $response;
    }

    public function getParams( $params = array() ) {
        if ( $params ) {
            return $params;
        } else {
            // Define supported fields explicitly, excluding unsupported ones like thumbnail_url
            $fields = [
                Fields::ID,
                Fields::CAPTION,
                Fields::COMMENTS_COUNT,
                Fields::LIKE_COUNT,
                Fields::MEDIA_TYPE,
                Fields::MEDIA_URL,
                Fields::PERMALINK,
                Fields::TIMESTAMP,
                Fields::CHILDREN . '{' . Fields::getDefaultMediaChildrenFields() . '}'
            ];
            $params[Params::FIELDS] = implode(',', $fields);
            $params[Params::USER_ID] = $this->userId;
            return $params;
        }
    }
}
?>