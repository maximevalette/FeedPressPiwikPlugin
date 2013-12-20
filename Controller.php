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

	public function subscribersWidget()
	{

		if ($token = Option::get('FeedPress.token.' . Common::getRequestVar('idSite'))) {

			if ($feedId = Option::get('FeedPress.feedId.' . Common::getRequestVar('idSite'))) {

				return $this->echoSubscribersGraph();

			} else {

				// sélection du feed form get action

				$json = $this->apiCall('account');

				if (count($json['feeds']) == 1) {

					Option::set('FeedPress.feedId.' . Common::getRequestVar('idSite'), $json['feeds'][0]['name'], 1);

					return $this->echoSubscribersGraph();

				} else {

					$html = '<div style="padding: 1em;">';
					$html .= '<p>Select the feed to display:</p>';

					$html .= '<ul style="padding-left: 2em;">';

					foreach ($json['feeds'] as $feed) {

						$html .= '<li><a href="?module=FeedPress&action=saveFeedId&idSite=' . Common::getRequestVar('idSite', 1, 'int') . '&feedId=' . $feed['name'] . '">/' . $feed['name'] . '</a> (' . $feed['subscribers'] . ' subscribers)</li>';

					}

					$html .= '</ul>';

					$html .= '</div>';

					return $html;

				}

			}

		} else {

			$args = array(
				'callback' => '51fe20f0a90b5',
				'next' => Url::getCurrentUrlWithoutQueryString() . '?module=FeedPress&action=saveToken&idSite=' . Common::getRequestVar('idSite', 1, 'int')
			);

			$html = '<p style="padding: 1em; text-align: center;"><a href="https://feedpress.it/auth?' . http_build_query($args) . '">' . Piwik::translate('FeedPress_LoginLink') . '</a></p>';

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

		$view->config->show_footer_message = '<p style="text-align: right; font-size: 10px; font-style: italic;">' . Piwik::translate('FeedPress_GraphDetails', array(Option::get('FeedPress.feedId.' . Common::getRequestVar('idSite')))) . ' — <a href="?module=FeedPress&action=deleteToken&idSite=' . Common::getRequestVar('idSite', 1, 'int') . '">' . Piwik::translate('FeedPress_LogoutLink') .'</a></p>';
		$view->config->title = Piwik::translate('FeedPress_GraphDetails', array(Option::get('FeedPress.feedId.' . Common::getRequestVar('idSite'))));

		$view->config->addTranslation('subscribers', Piwik::translate('FeedPress_Subscribers'));

		return $this->renderView($view);

	}

	function saveToken()
	{

		$token = Common::getRequestVar('token', '', 'string');

		if (Piwik::getCurrentUserLogin() != 'anonymous') {

			Option::set('FeedPress.token.' . Common::getRequestVar('idSite'), $token, 1);

		}

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	function saveFeedId()
	{

		$feedId = Common::getRequestVar('feedId', '', 'string');

		if (Piwik::getCurrentUserLogin() != 'anonymous') {

			Option::set('FeedPress.feedId.' . Common::getRequestVar('idSite'), $feedId, 1);

		}

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	function deleteToken()
	{

		if (Piwik::getCurrentUserLogin() != 'anonymous') {

			Option::delete('FeedPress.token.' . Common::getRequestVar('idSite'));
			Option::delete('FeedPress.feedId.' . Common::getRequestVar('idSite'));

		}

		header('Location: ' . SettingsPiwik::getPiwikUrl());
		exit;

	}

	function apiCall($url, $args=array())
	{

		$token = Option::get('FeedPress.token.' . Common::getRequestVar('idSite'));

		$args = array_merge($args, array(
				'key' => '51fe20f0a90b5',
				'token' => $token
			)
		);

		$data = Http::sendHttpRequest('http://api.feedpress.it/' . $url . '.json?' . http_build_query($args), 5, 'FeedPress Piwik Plugin/1.0.1 (+http://www.feedpress.it)');

		$json = json_decode($data, true);

		return $json;

	}

}
