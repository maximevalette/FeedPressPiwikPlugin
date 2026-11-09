<?php
/**
 * FeedPress Piwik Plugin
 *
 * @link http://feedpress.it
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_FeedPress
 */
namespace Piwik\Plugins\FeedPress;

use Piwik\View;
use Piwik\Option;
use Piwik\Common;
use Piwik\Url;
use Piwik\Piwik;
use Piwik\Nonce;
use Piwik\SettingsPiwik;
use Piwik\ViewDataTable;
use Piwik\DataTable;
use Piwik\Period;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;
use Piwik\Http;

/**
 *
 * @package FeedPress
 */
class Controller extends \Piwik\Plugin\Controller
{

	const API_URL = 'https://api.feedpress.com/';

	const API_KEY = '51fe20f0a90b5';

	/**
	 * Lifetime of the signed state used for the saveToken callback. The user
	 * logs in on feedpress.it in between, so it must outlive a form nonce.
	 */
	const SAVE_TOKEN_STATE_TTL = 3600;

	public function subscribersWidget()
	{

		$idSite = $this->getIdSite();

		Piwik::checkUserHasViewAccess($idSite);

		if ($token = Option::get('FeedPress.token.' . $idSite)) {

			if ($feedId = Option::get('FeedPress.feedId.' . $idSite)) {

				return $this->echoSubscribersGraph();

			} else {

				// Only website admins can pick the feed to display.
				Piwik::checkUserHasAdminAccess($idSite);

				$json = $this->apiCall($idSite, 'account');

				if (empty($json['feeds']) || !is_array($json['feeds'])) {

					return '<p style="padding: 1em; text-align: center;">' . Piwik::translate('FeedPress_ApiError') . '</p>';

				}

				if (count($json['feeds']) == 1) {

					Option::set('FeedPress.feedId.' . $idSite, $json['feeds'][0]['name'], 1);

					return $this->echoSubscribersGraph();

				} else {

					$nonce = Nonce::getNonce(self::getNonceId('saveFeedId', $idSite));

					$html = '<div style="padding: 1em;">';
					$html .= '<p>Select the feed to display:</p>';

					$html .= '<ul style="padding-left: 2em;">';

					foreach ($json['feeds'] as $feed) {

						$url = '?' . http_build_query(array(
							'module' => 'FeedPress',
							'action' => 'saveFeedId',
							'idSite' => $idSite,
							'feedId' => $feed['name'],
							'nonce'  => $nonce,
						));

						$html .= '<li><a href="' . self::escape($url) . '">/' . self::escape($feed['name']) . '</a> (' . (int) $feed['subscribers'] . ' subscribers)</li>';

					}

					$html .= '</ul>';

					$html .= '</div>';

					return $html;

				}

			}

		} else {

			// Only website admins can connect a FeedPress account.
			if (!Piwik::isUserHasAdminAccess($idSite)) {

				return '';

			}

			$next = Url::getCurrentUrlWithoutQueryString() . '?' . http_build_query(array(
				'module' => 'FeedPress',
				'action' => 'saveToken',
				'idSite' => $idSite,
				'state'  => self::buildState($idSite, time()),
			));

			$args = array(
				'callback' => self::API_KEY,
				'next' => $next
			);

			$html = '<p style="padding: 1em; text-align: center;"><a href="' . self::escape('https://feedpress.it/auth?' . http_build_query($args)) . '">' . Piwik::translate('FeedPress_LoginLink') . '</a></p>';

			return $html;

		}

	}

	function echoSubscribersGraph()
	{

		/*$view = ViewDataTable\Factory::build('table', 'FeedPress.getSubscribersEvolution', __FUNCTION__);
		$view->config->translations['subscribers'] = Piwik::translate('FeedPress_Subscribers');*/

		$view = new View('@FeedPress/index');
		$this->setPeriodVariablesView($view);

		$view->graphSubscribersSummary = $this->getSubscribersGraph();

		return $view->render();

	}

	function getSubscribersGraph()
	{

		$idSite = $this->getIdSite();

		Piwik::checkUserHasViewAccess($idSite);

		$view = ViewDataTable\Factory::build(
		                             Evolution::ID,
			                             'FeedPress.getSubscribers',
			                             'FeedPress.getSubscribersGraph'
		);

		$view->config->show_limit_control = false;
		$view->config->show_search = false;
		$view->config->show_goals = false;
		$view->config->columns_to_display = array('subscribers');
		$view->config->show_exclude_low_population = false;
		$view->config->enable_sort = false;
		$view->config->show_offset_information = false;
		$view->config->show_pagination_control = false;
		$view->requestConfig->filter_limit = 10;
		$view->config->max_graph_elements = false;
		$view->config->show_line_graph = true;
		$view->config->show_tag_cloud = false;
		$view->config->show_pie_chart = false;
		$view->config->show_table = false;
		$view->config->show_series_picker = false;
		$view->config->hide_annotations_view = true;

		$graphDetails = Piwik::translate('FeedPress_GraphDetails', array(self::escape(Option::get('FeedPress.feedId.' . $idSite))));

		$footer = '<p style="text-align: right; font-size: 10px; font-style: italic;">' . $graphDetails;

		if (Piwik::isUserHasAdminAccess($idSite)) {

			$logoutUrl = '?' . http_build_query(array(
				'module' => 'FeedPress',
				'action' => 'deleteToken',
				'idSite' => $idSite,
				'nonce'  => Nonce::getNonce(self::getNonceId('deleteToken', $idSite)),
			));

			$footer .= ' — <a href="' . self::escape($logoutUrl) . '">' . Piwik::translate('FeedPress_LogoutLink') . '</a>';

		}

		$footer .= '</p>';

		$view->config->show_footer_message = $footer;
		$view->config->title = $graphDetails;

		$view->config->addTranslation('subscribers', Piwik::translate('FeedPress_Subscribers'));

		return $this->renderView($view);

	}

	function saveToken()
	{

		$idSite = $this->getIdSite();

		Piwik::checkUserHasAdminAccess($idSite);
		$this->checkState($idSite);

		$token = Common::getRequestVar('token', '', 'string');

		if ($token !== '') {

			Option::set('FeedPress.token.' . $idSite, $token, 1);

		}

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	function saveFeedId()
	{

		$idSite = $this->getIdSite();

		Piwik::checkUserHasAdminAccess($idSite);
		$this->checkNonce('saveFeedId', $idSite);

		$feedId = Common::getRequestVar('feedId', '', 'string');

		if ($feedId !== '') {

			Option::set('FeedPress.feedId.' . $idSite, $feedId, 1);

		}

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	function deleteToken()
	{

		$idSite = $this->getIdSite();

		Piwik::checkUserHasAdminAccess($idSite);
		$this->checkNonce('deleteToken', $idSite);

		Option::delete('FeedPress.token.' . $idSite);
		Option::delete('FeedPress.feedId.' . $idSite);

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	/**
	 * Calls the FeedPress API.
	 *
	 * Returns the decoded JSON, or null on any transport or decoding error.
	 * The underlying error is never sent back to the caller.
	 */
	function apiCall($idSite, $url, $args=array())
	{

		$token = Option::get('FeedPress.token.' . (int) $idSite);

		if (empty($token)) {

			return null;

		}

		$args = array_merge($args, array(
				'key' => self::API_KEY,
				'token' => $token
			)
		);

		try {

			$data = Http::sendHttpRequest(self::API_URL . $url . '.json?' . http_build_query($args), 5, 'FeedPress Piwik Plugin/1.0.3 (+https://feedpress.com)');

		} catch (\Exception $e) {

			return null;

		}

		$json = json_decode($data, true);

		return is_array($json) ? $json : null;

	}

	private function getIdSite()
	{

		return Common::getRequestVar('idSite', 1, 'int');

	}

	private function checkNonce($action, $idSite)
	{

		$nonceId = self::getNonceId($action, $idSite);
		$nonce = Common::getRequestVar('nonce', '', 'string');

		if (!Nonce::verifyNonce($nonceId, $nonce)) {

			throw new \Exception('Invalid or expired request, please try again.');

		}

		Nonce::discardNonce($nonceId);

	}

	/**
	 * Piwik\Nonce rejects requests whose referrer is not local, which is always
	 * the case when coming back from feedpress.it. The saveToken callback is
	 * protected with a state bound to the user, the website and the instance
	 * salt instead.
	 */
	private static function buildState($idSite, $timestamp)
	{

		$payload = Piwik::getCurrentUserLogin() . '|' . (int) $idSite . '|' . (int) $timestamp;

		return (int) $timestamp . '.' . hash_hmac('sha256', $payload, 'FeedPress.saveToken.' . SettingsPiwik::getSalt());

	}

	private function checkState($idSite)
	{

		$state = Common::getRequestVar('state', '', 'string');
		$parts = explode('.', $state, 2);
		$timestamp = (int) $parts[0];

		$valid = count($parts) == 2
			&& $timestamp > time() - self::SAVE_TOKEN_STATE_TTL
			&& $timestamp <= time()
			&& self::safeEquals(self::buildState($idSite, $timestamp), $state);

		if (!$valid) {

			throw new \Exception('Invalid or expired request, please try again.');

		}

	}

	private static function safeEquals($known, $user)
	{

		if (function_exists('hash_equals')) {

			return hash_equals($known, $user);

		}

		if (strlen($known) !== strlen($user)) {

			return false;

		}

		$result = 0;

		for ($i = 0; $i < strlen($known); $i++) {

			$result |= ord($known[$i]) ^ ord($user[$i]);

		}

		return $result === 0;

	}

	private static function getNonceId($action, $idSite)
	{

		return 'FeedPress.' . $action . '.' . (int) $idSite;

	}

	private static function escape($value)
	{

		return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

	}

}
