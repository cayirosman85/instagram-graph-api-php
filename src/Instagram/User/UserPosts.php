<?php
namespace Instagram\User;

use Instagram\Instagram;
use Instagram\Request\Params;
use Instagram\Request\Fields;
use Instagram\Request\Request;
use Instagram\Media\Insights; // Added for insights

class UserPosts extends User {
    protected $username;

    protected $mediaFields = array(
        Fields::ID,
        Fields::USERNAME,
        Fields::CAPTION,
        Fields::LIKE_COUNT,
        Fields::COMMENTS_COUNT,
        Fields::TIMESTAMP,
        Fields::MEDIA_TYPE,
        Fields::PERMALINK,
        Fields::MEDIA_URL,
        Fields::THUMBNAIL_URL,
        Fields::CHILDREN, // Added to fetch carousel children
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
    
        $fetchInsights = $params['fetch_insights'] ?? false; // New parameter to control insights fetching
    
        if (isset($response['business_discovery']['media']['data'])) {
            foreach ($response['business_discovery']['media']['data'] as &$media) {
                $mediaId = $media['id'];
    
                // Fetch comments if present
                if (isset($media['comments_count']) && $media['comments_count'] > 0) {
                    $comments = $this->fetchMediaComments($mediaId);
                    if ($comments) {
                        $media['comments'] = $comments;
                    }
                }
    
                // Fetch insights only if explicitly requested
                if ($fetchInsights) {
                    $insights = $this->fetchMediaInsights($mediaId, $media['media_type'] ?? 'UNKNOWN');
                    if ($insights) {
                        $media['insights'] = $insights;
                    }
                }
    
                // Fetch children for CAROUSEL_ALBUM
                if ($media['media_type'] === 'CAROUSEL_ALBUM') {
                    $children = $this->fetchCarouselChildren($mediaId);
                    if ($children) {
                        $media['children'] = $children;
                    }
                }
            }
            unset($media);
        }
    
        $this->calcNextLink($response);
        $this->setPrevNextLinks($response);
    
        return $response;
    }

    protected function fetchMediaComments($mediaId) {
        $endpoint = "/$mediaId";
        $params = [
            'fields' => 'comments{id,text,username,user,like_count,timestamp,replies{id,text,username,timestamp}}',
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

    protected function fetchCarouselChildren($mediaId) {
        $endpoint = "/$mediaId";
        $params = [
            'fields' => 'children{media_type,media_url,thumbnail_url}',
            'access_token' => $this->accessToken
        ];

        $response = $this->get(['endpoint' => $endpoint, 'params' => $params]);
        if (isset($response['error'])) {
            error_log("Error fetching children for media $mediaId: " . json_encode($response['error']));
            return null;
        }
        return isset($response['children']) ? $response['children'] : null;
    }

 
    public function calcNextLink(&$response) {
        if (isset($response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::AFTER])) {
          $fieldsString = $this->getParams();
          $snippet = Fields::MEDIA . '.' . Params::AFTER . '(' . $response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING][Fields::CURSORS][Params::AFTER] . '){';
          $newFieldsParams = str_replace(Fields::MEDIA . '{', $snippet, $fieldsString);
          $endpoint = '/' . $this->userId . '/';
          $request = new Request(Request::METHOD_GET, $endpoint, $newFieldsParams, $this->graphVersion, $this->accessToken);
          $response[Fields::PAGING][Params::NEXT] = $request->getUrl(); // Set next URL
        }
      }
    
      public function setPrevNextLinks(&$response) {
        // Ensure paging is fully preserved
        if (isset($response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING])) {
          $response[Fields::PAGING] = $response[Fields::BUSINESS_DISCOVERY][Fields::MEDIA][Fields::PAGING];
        }
      }
    public function getMediaPage($page) {
        $pageUrl = $this->pagingNextLink;
        $mediaPageRequest = $this->sendCustomRequest($pageUrl);
        $this->calcNextLink($mediaPageRequest);
        $this->setPrevNextLinks($mediaPageRequest);
        return $mediaPageRequest;
    }

    public function getParams($params = array()) {
        if (!empty($params)) {
            $fieldsString = Fields::BUSINESS_DISCOVERY . '.' . Fields::USERNAME . '(' . $this->username . '){' .
                Fields::MEDIA;
            if (isset($params['limit'])) {
                $fieldsString .= ".limit({$params['limit']})";
            }
            if (isset($params['after'])) {
                $fieldsString .= ".after({$params['after']})";
            }
            $fieldsString .= '{' . Params::commaImplodeArray($this->mediaFields) . '}}';
            return Params::getFieldsParam($fieldsString, false);
        } else {
            $fieldsString = Fields::BUSINESS_DISCOVERY . '.' . Fields::USERNAME . '(' . $this->username . '){' .
                Fields::MEDIA . '{' .
                    Params::commaImplodeArray($this->mediaFields) .
                '}' .
            '}';
            return Params::getFieldsParam($fieldsString, false);
        }
    }
}