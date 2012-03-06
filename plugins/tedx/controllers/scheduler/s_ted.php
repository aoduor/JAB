<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Ted Scheduler Controller
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @subpackage Scheduler
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
*/

class S_Ted_Controller extends Controller {
	
	public function __construct()
    {
        parent::__construct();
	}
	
	/**
	 * parse feed and send feed items to database
	 */
	public function index()
	{
		// Max number of feeds to keep
		$max_items = 100;
		
		// Today's Date
		$today = strtotime('now');
		
		// Get All Feeds From DB
		$feeds = ORM::factory('feed')->like('feed_name', 'TED')->find_all();
		foreach ($feeds as $feed)
		{
			// Get Feed Items with location but no incident yet
			$feed_items = ORM::factory('feed_item')->where(array('feed_id' => $feed->id,'location_id !=' => 0, 'incident_id' => 0))->find_all();
			foreach ($feed_items as $feed_item) 
			{
				//echo $feed_item->item_title;
				$incident = new Incident_Model();
				$incident->incident_title = $feed_item->item_title;
				$incident->incident_description = $feed_item->item_description;
				$incident->incident_date = $feed_item->item_date;
				$incident->location_id = $feed_item->location_id;
				$incident->incident_active = true;
				$incident->incident_verified = true;

				if (strpos($feed_item->item_link,'youtube') !== FALSE)
				{
					$id = str_replace(array('http://www.youtube.com/watch?v=','&amp;feature=youtube_gdata'),'',$feed_item->item_link);
					
					// Get extra details from youtube api
					$json = @file_get_contents("http://gdata.youtube.com/feeds/api/videos/$id?v=2&alt=jsonc");
					if ($json !== FALSE) {
						$json = json_decode($json);
						$thumb = $json->data->thumbnail->hqDefault;
						if (! $incident->incident_description)
						{
							$incident->incident_description = $json->data->description;
						}
						foreach ($json->data->tags as $tag)
						{
							if (stripos($tag,'tedx') !== FALSE && strtolower($tag) != 'tedx') {
								$cat = $tag;
								break;
							}
						}
					}

					$incident->save();
					$feed_item->incident_id = $incident->id;
					$feed_item->save();

					// Add video
					$video = new Media_Model();
					$video->location_id = $incident->location_id;
					$video->incident_id = $incident->id;
					$video->media_type = 2;		// Video
					$video->media_link = $feed_item->item_link;
					$video->media_thumb = isset($thumb) ? $thumb : '';
					$video->media_medium = isset($thumb) ? $thumb : '';
					$video->media_date = $feed_item->item_date;
					$video->save();
					
					// News Link
					$news = new Media_Model();
					$news->location_id = $incident->location_id;
					$news->incident_id = $incident->id;
					$news->media_type = 4;		// News
					$news->media_link = $feed_item->item_link;
					$news->media_date = $feed_item->item_date;
					$news->save();
					
					// Category
					if (! empty($cat)) {
						$db = Database::instance();
						$result = $db->query("SELECT `category`.`id` FROM `category` WHERE lower(`category_title`) = ? ORDER BY `category`.`category_position` ASC LIMIT 0, 1", strtolower($cat));
						if($row = $result->current()) {
							$category_id = $row->id;
						} else {
							$category = new Category_Model();
							$category->category_title = $cat;
							// We'll just use blue since its tedx
							$category->category_color = '002bff'; 
							// because all current categories are of type '5'
							$category->category_type = 5; 
							$category->category_visible = 1;
							$category->category_description = $cat;
							$category->parent_id = 156; // TEDX
							$category->save();
							$category_id = $category->id;
						}
						
						$incident_category = new Incident_Category_Model();
						$incident_category->incident_id = $incident->id;
						$incident_category->category_id = $category_id;
						$incident_category->save();
					}
				}
			}
		}
	}
}
