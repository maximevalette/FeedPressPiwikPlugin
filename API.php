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

use Piwik\Date;
use Piwik\View;
use Piwik\Option;
use Piwik\Common;
use Piwik\ViewDataTable;
use Piwik\DataTable;
use Piwik\Period;
use Piwik\DataTable\Row;
use Piwik\DataTable\Map;
use Piwik\Period\Day;

class API extends \Piwik\Plugin\API
{
	public function getSubscribers()
	{

		$fC = new Controller();
		$json = $fC->apiCall('feeds/subscribers', array('feed' => Option::get('FeedPress.feedId.' . Common::getRequestVar('idSite'))));

		$period = Period\Range::factory('day', 'last10');
		$i = 0;
		$map = new Map();

		foreach ($period->getSubperiods() as $subPeriod) {

			$subscribers = 0;

			if (isset($json['stats'][9 - $i]['greader']) && isset($json['stats'][9 - $i]['newsletter'])) {
				$subscribers = $json['stats'][9 - $i]['greader'] + $json['stats'][9 - $i]['newsletter'];
			}

			$dataTable = new DataTable();

			$dataTable->addRow(new Row(
					array(
						Row::COLUMNS => array('subscribers' => $subscribers),
					)
				)
			);

			$dataTable->setMetadata('period', new Day(Date::factory($subPeriod->toString())));
			$dataTable->setMetadata('day', new Day(Date::factory($subPeriod->toString())));

			$map->addTable($dataTable, $subPeriod->toString());

			$i++;

		}

		$map->setKeyName('date');

		return $map;

	}
}