<?php
/**
 * FeedPress Matomo Plugin
 *
 * @link https://feedpress.com
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\FeedPress;

use Piwik\Date;
use Piwik\DataTable;
use Piwik\DataTable\Map;
use Piwik\DataTable\Row;
use Piwik\Period;
use Piwik\Period\Day;
use Piwik\Piwik;
use Piwik\Site;

class API extends \Piwik\Plugin\API
{
    const DAYS = 10;

    /**
     * Feed subscribers for the last 10 days.
     *
     * @param int $idSite
     * @return Map
     */
    public function getSubscribers($idSite)
    {
        $idSite = (int) $idSite;

        // Subscriber stats are only for users who can view this website.
        Piwik::checkUserHasViewAccess($idSite);

        $stats = array();
        $feedId = FeedPressClient::getFeedId($idSite);

        if ($feedId) {
            $json = FeedPressClient::call($idSite, 'feeds/subscribers', array('feed' => $feedId));

            if (isset($json['stats']) && is_array($json['stats'])) {
                $stats = $json['stats'];
            }
        }

        // The API returns the last 30 days: keep the most recent ones, newest first.
        usort($stats, function ($a, $b) {
            return (isset($b['day']) ? (int) $b['day'] : 0) - (isset($a['day']) ? (int) $a['day'] : 0);
        });

        $site = new Site($idSite);
        $period = Period\Factory::build('day', 'last' . self::DAYS, $site->getTimezone());
        $subPeriods = $period->getSubperiods();
        $count = count($subPeriods);

        $map = new Map();
        $map->setKeyName('date');

        foreach ($subPeriods as $i => $subPeriod) {
            $stat = isset($stats[$count - 1 - $i]) ? $stats[$count - 1 - $i] : array();

            $subscribers = (isset($stat['greader']) ? (int) $stat['greader'] : 0)
                + (isset($stat['newsletter']) ? (int) $stat['newsletter'] : 0);

            $dataTable = new DataTable();
            $dataTable->addRow(new Row(array(
                Row::COLUMNS => array('subscribers' => $subscribers),
            )));

            $day = new Day(Date::factory($subPeriod->toString()));
            $dataTable->setMetadata('period', $day);
            // Required by the Evolution graph (DataTableFactory::TABLE_METADATA_SITE_INDEX
            // and DataTable::ARCHIVE_STATE_METADATA_NAME).
            $dataTable->setMetadata('site', $site);
            $dataTable->setMetadata('archive_state', 'complete');

            $map->addTable($dataTable, $subPeriod->toString());
        }

        return $map;
    }
}
