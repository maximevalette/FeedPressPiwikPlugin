<?php
/**
 * FeedPress Matomo Plugin
 *
 * @link https://feedpress.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FeedPress;

use Piwik\Http;
use Piwik\Option;

/**
 * Minimal FeedPress API client. Kept out of the Controller so it cannot be
 * reached as a controller action.
 */
class FeedPressClient
{
    const API_URL = 'https://api.feedpress.com/';

    const API_KEY = '51fe20f0a90b5';

    public static function getToken($idSite)
    {
        return Option::get('FeedPress.token.' . (int) $idSite);
    }

    public static function getFeedId($idSite)
    {
        return Option::get('FeedPress.feedId.' . (int) $idSite);
    }

    /**
     * Returns the decoded JSON, or null on any transport or decoding error.
     * The underlying error is never sent back to the caller.
     */
    public static function call($idSite, $endpoint, array $args = array())
    {
        $token = self::getToken($idSite);

        if (empty($token)) {
            return null;
        }

        $args = array_merge($args, array(
            'key'   => self::API_KEY,
            'token' => $token,
        ));

        try {
            $data = Http::sendHttpRequest(
                self::API_URL . $endpoint . '.json?' . http_build_query($args),
                5,
                'FeedPress Matomo Plugin/2.0.0 (+https://feedpress.com)'
            );
        } catch (\Exception $e) {
            return null;
        }

        $json = json_decode((string) $data, true);

        return is_array($json) ? $json : null;
    }
}
