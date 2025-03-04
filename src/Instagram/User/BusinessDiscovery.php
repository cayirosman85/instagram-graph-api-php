<?php

namespace Instagram\User;

use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;
use Instagram\Request\Request;
use Instagram\User\Media;
use Instagram\Media\Insights;

class BusinessDiscovery extends User {
    protected $username;

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

    public function __construct($config) {
        parent::__construct($config);
        $this->username = $config['username'];
    }

    public function getSelf($params = array()) {
        $getParams = array(
            'endpoint' => '/' . $this->userId,
            'params' => $this->getParams($params)
        );

        $response = $this->get($getParams);

        if (isset($response['error'])) {
            error_log("Error in getSelf: " . json_encode($response['error']));
            return $response;
        }

        if (isset($response['business_discovery']['media']['data'])) {
            foreach ($response['business_discovery']['media']['data'] as &$media) {
                $mediaId = $media['id'];

                if (isset($media['comments_count']) && $media['comments_count'] > 0) {
                    $comments = $this->fetchMediaComments($mediaId);
                    if ($comments) {
                        $media['comments'] = $comments;
                    }
                }

                $insights = $this->fetchMediaInsights($mediaId, $media['media_type'] ?? 'UNKNOWN');
                if ($insights) {
                    $media['insights'] = $insights;
                }
            }
            unset($media);
        }

        if (isset($response['business_discovery']['tags']['data'])) {
            foreach ($response['business_discovery']['tags']['data'] as &$tag) {
                $mediaId = $tag['id'];

                if (isset($tag['comments_count']) && $tag['comments_count'] > 0) {
                    $comments = $this->fetchMediaComments($mediaId);
                    if ($comments) {
                        $tag['comments'] = $comments;
                    }
                }

                $insights = $this->fetchMediaInsights($mediaId, $tag['media_type'] ?? 'UNKNOWN');
                if ($insights) {
                    $tag['insights'] = $insights;
                }
            }
            unset($tag);
        }

        $this->calcNextLink($response);
        $this->setPrevNextLinks($response);

        return $response;
    }

    protected function fetchMediaComments($mediaId) {
        $endpoint = "/$mediaId";
        $params = [
            'fields' => 'comments{id,text,username,user,like_count,timestamp,replies{id,text,username,timestamp}}', // Added replies with subfields
            'access_token' => $this->accessToken
        ];

        $response = $this->get(['endpoint' => $endpoint, 'params' => $params]);
        if (isset($response['error'])) {
            error_log("Error fetching comments for media $mediaId: " . json_encode($response['error']));
            return null;
        }
        return isset($response['comments']) ? $response['comments'] : null;
    }

    protected function fetchMediaInsights($mediaId, $mediaType) {
        // Verify media exists and is accessible
        $check = $this->get(['endpoint' => "/$mediaId", 'params' => ['fields' => 'id,media_type', 'access_token' => $this->accessToken]]);
        if (isset($check['error'])) {
            error_log("Media $mediaId not accessible: " . json_encode($check['error']));
            return null;
        }

        $insightsObj = new Insights([
            'media_id' => $mediaId,
            'media_type' => $mediaType,
            'access_token' => $this->accessToken,
            'user_id' => $this->userId
        ]);

        $response = $insightsObj->getSelf();
        if (isset($response['error'])) {
            error_log("Error fetching insights for media $mediaId: " . json_encode($response['error']));
            return null;
        }
        return isset($response['data']) ? $response['data'] : null;
    }

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

    public function getMediaPage($page) {
        $pageUrl = Params::NEXT == $page ? $this->pagingNextLink : $this->pagingPreviousLink;
        $mediaPageRequest = $this->sendCustomRequest($pageUrl);
        $this->calcNextLink($mediaPageRequest);
        $this->setPrevNextLinks($mediaPageRequest);
        return $mediaPageRequest;
    }

    public function getParams($params = array()) {
        if ($params) {
            return $params;
        } else {
            $fieldsString = Fields::BUSINESS_DISCOVERY . '.' . Fields::USERNAME . '(' . $this->username . '){' .
                Params::commaImplodeArray($this->fields) . ',' .
                Fields::MEDIA . '.limit(8){' . // Limit to 8 media items
                    Params::commaImplodeArray($this->mediaFields) . ',' .
                    Fields::CHILDREN . '{' .
                        Fields::getDefaultMediaChildrenFields() .
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