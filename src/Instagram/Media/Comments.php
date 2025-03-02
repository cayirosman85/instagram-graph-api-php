<?php
namespace Instagram\User;

use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;
use Instagram\Request\Request;
use Instagram\User\Media;

class BusinessDiscovery extends User {
    /**
     * @var integer $username Instagram username to get info on.
     */
    protected $username;

    /**
     * @var array $fields a list of all the fields we are requesting to get back for the business.
     */
    protected $fields = array(
        Fields::USERNAME,
        Fields::WEBSITE,
        Fields::NAME,
        Fields::IG_ID,
        Fields::ID,
        Fields::PROFILE_PICTURE_URL,
        Fields::BIOGRAPHY,
        Fields::FOLLOWS_COUNT,
        Fields::FOLLOWERS_COUNT,
        Fields::MEDIA_COUNT
    );

    /**
     * @var array $mediaFields a list of all the fields we are requesting to get back for each media object.
     */
    protected $mediaFields = array(
        Fields::ID,
        Fields::USERNAME,
        Fields::CAPTION,
        Fields::LIKE_COUNT,
        Fields::COMMENTS_COUNT,
        Fields::TIMESTAMP,
        Fields::MEDIA_PRODUCT_TYPE,
        Fields::MEDIA_TYPE,
        Fields::OWNER,
        Fields::PERMALINK,
        Fields::MEDIA_URL,
        Fields::THUMBNAIL_URL,
    );

    /**
     * @var array $storyFields a list of all the fields we are requesting to get back for each story object.
     */
    protected $storyFields = array(
        Fields::ID,
        Fields::MEDIA_TYPE,
        Fields::MEDIA_URL,
        Fields::THUMBNAIL_URL,
        Fields::OWNER,
        Fields::PERMALINK,
        Fields::CAPTION,
        Fields::LIKE_COUNT,
        Fields::COMMENTS_COUNT,
        Fields::TIMESTAMP
    );

    /**
     * @var array $tagFields a list of all the fields we are requesting to get back for each tagged media object.
     */
    protected $tagFields = array(
        Fields::ID,
        Fields::USERNAME,
        Fields::CAPTION,
        Fields::LIKE_COUNT,
        Fields::COMMENTS_COUNT,
        Fields::TIMESTAMP,
        Fields::MEDIA_TYPE,
        Fields::PERMALINK,
        Fields::MEDIA_URL
    );

    /**
     * @var array $commentFields a list of all the fields we are requesting to get back for each comment object.
     */
    protected $commentFields = array(
        Fields::ID,
        Fields::TEXT,
        Fields::USERNAME,
        Fields::LIKE_COUNT,
        Fields::TIMESTAMP
    );

    /**
     * Constructor for instantiating a new object.
     *
     * @param array $config for the class.
     * @return void
     */
    public function __construct($config) {
        parent::__construct($config);
        $this->username = $config['username'];
    }

    /**
     * Get the users account business discovery information and posts.
     *
     * @param array $params Params for the GET request.
     * @return Instagram Response.
     */
    public function getSelf($params = array()) {
        $getParams = array(
            'endpoint' => '/' . $this->userId,
            'params' => $this->getParams($params)
        );

        $response = $this->get($getParams);
        $this->calcNextLink($response);
        $this->setPrevNextLinks($response);

        return $response;
    }

    /**
     * Calculate next link based on the cursors.
     *
     * @param array $response Instagram api response.
     * @return void
     */
    public function calcNextLink(&$response) {
        if (isset($response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::BEFORE])) {
            $fieldsString = $this->getParams();
            $snippet = Fields::MEDIA . '.' . Params::BEFORE . '(' . $response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::BEFORE] . '){';
            $newFieldsParams = str_replace(Fields::MEDIA . '{', $snippet, $fieldsString);
            $endpoint = '/' . $this->userId . '/';
            $request = new Request(Request::METHOD_GET, $endpoint, $newFieldsParams, $this->graphVersion, $this->accessToken);
            $response[Fields::PAGING][Params::PREVIOUS] = $request->getUrl();
        }

        if (isset($response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::AFTER])) {
            $fieldsString = $this->getParams();
            $snippet = Fields::MEDIA . '.' . Params::AFTER . '(' . $response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::AFTER] . '){';
            $newFieldsParams = str_replace(Fields::MEDIA . '{', $snippet, $fieldsString);
            $endpoint = '/' . $this->userId . '/';
            $request = new Request(Request::METHOD_GET, $endpoint, $newFieldsParams, $this->graphVersion, $this->accessToken);
            $response[Fields::PAGING][Params::NEXT] = $request->getUrl();
        }
    }

    /**
     * Request previous or next page data.
     *
     * @param string $page specific page to request.
     * @return array of previous or next page data.
     */
    public function getMediaPage($page) {
        $pageUrl = Params::NEXT == $page ? $this->pagingNextLink : $this->pagingPreviousLink;
        $mediaPageRequest = $this->sendCustomRequest($pageUrl);
        $this->calcNextLink($mediaPageRequest);
        $this->setPrevNextLinks($mediaPageRequest);

        return $mediaPageRequest;
    }

    /**
     * Get params for the request.
     *
     * @param array $params specific params for the request.
     * @return array of params for the request.
     */
    public function getParams($params = array()) {
        if ($params) {
            return $params;
        } else {
            $fieldsString = Fields::BUSINESS_DISCOVERY . '.' . Fields::USERNAME . '(' . $this->username . '){' .
                Params::commaImplodeArray($this->fields) . ',' .
                Fields::MEDIA . '{' .
                    Params::commaImplodeArray($this->mediaFields) . ',' .
                    Fields::CHILDREN . '{' .
                        Fields::getDefaultMediaChildrenFields() .
                    '},' .
                    Fields::COMMENTS . '{' . // Add comments here
                        Params::commaImplodeArray($this->commentFields) .
                    '}' .
                '},' .
                Fields::STORIES . '{' .
                    Params::commaImplodeArray($this->storyFields) .
                '},' .
                Fields::TAGS . '{' .
                    Params::commaImplodeArray($this->tagFields) .
                '}' .
            '}';

            return Params::getFieldsParam($fieldsString, false);
        }
    }
}
?>