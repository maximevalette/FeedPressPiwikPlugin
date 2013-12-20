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

use Piwik\WidgetsList;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Evolution;

/**
 * @package FeedPress
 */
class FeedPress extends \Piwik\Plugin
{
    /**
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'WidgetsList.addWidgets' => 'addWidgets'
        );
    }

	public function getDefaultTypeViewDataTable(&$defaultViewTypes)
	{
		$defaultViewTypes['FeedPress.subscribersWidget'] = Evolution::ID;
	}

	public function addWidgets()
	{
		WidgetsList::add('VisitsSummary_VisitsSummary', 'FeedPress Subscribers', 'FeedPress', 'subscribersWidget');
	}
}
