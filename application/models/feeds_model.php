<?php
class Feeds_Model extends CI_Model {
	function selectToList($num, $offset, $filter = null, $statusId = null, $countryId = null, $langId = null, $tagId = null, $userId = null, $feedSuggest = null, $orderBy = '', $orderDir = ''){
		$languages = null;
		if ($langId != null) { // Busca lenguages relacionados: si el filtro esta seteado en 'en', trae resultados con 'en-us', 'en-uk', etc tambien
			// TODO: poner un ckeckbox para definir si queres aplicar el filtro asi o no
			$this->load->model('Languages_Model');
			$languages = $this->Languages_Model->getRelatedLangs($langId);
		}
				
		$this->db
			->select('SQL_CALC_FOUND_ROWS feeds.feedId, feedName, feedDescription, feedUrl, feedLink, statusName, countryName, langName, feedLastScan, feedLastEntryDate', false)
			->join('status', 'status.statusId = feeds.statusId', 'left')
			->join('countries', 'countries.countryId = feeds.countryId', 'left')
			->join('languages', 'languages.langId = feeds.langId', 'left');
			
		if ($filter != null) {
			$this->db->like('feedName', $filter);
		}
		if ($statusId != null) {
			$this->db->where('feeds.statusId', $statusId);
		}
		if ($countryId != null) {
			$this->db->where('feeds.countryId', $countryId);
		}
		if ($languages != null) {
			$this->db->where('feeds.langId IN (\''.implode('\', \'', $languages).'\')' );
		}
		if ($tagId != null) {
			$this->db->join('feeds_tags', 'feeds_tags.feedId = feeds.feedId', 'inner');
			$this->db->where('feeds_tags.tagId', $tagId);
		}
		if ($userId != null) {
			$this->db->join('users_feeds', 'users_feeds.feedId = feeds.feedId', 'inner');
			$this->db->where('users_feeds.userId', $userId);
		}
		if ($feedSuggest == true) {
			$this->db->where('feeds.feedSuggest', true);
		}
		
		if (!in_array($orderBy, array( 'feedId', 'feedName', 'feedLastEntryDate', 'feedLastScan' ))) {
			$orderBy = 'feedId';
		}
		$this->db->order_by($orderBy, $orderDir == 'desc' ? 'desc' : 'asc');
		
		
		$query = $this->db->get('feeds', $num, $offset);
		//pr($this->db->last_query()); die;
						
		$query->foundRows = $this->Commond_Model->getFoundRows();
		return $query;
	}
	
	function select(){
		return $this->db->get('feeds');
	}

	function get($feedId, $getTags = false, $getIcon = false){
		$result = $this->db->where('feeds.feedId', $feedId)->get('feeds')->row_array();
		
		if ($getTags == true) {
			$result['aTagId'] = array();
			$query = $this->Tags_Model->selectByFeedId($feedId);
			foreach ($query as $data) {
				$result['aTagId'][] = array('id' => $data['tagId'], 'text' => $data['tagName']);
			}
		}


		if ($getIcon == true) {
			$result['feedIcon'] = (element('feedIcon', $result) == null ? site_url().'assets/images/default_feed.png' : site_url().'assets/favicons/'.element('feedIcon', $result));
		}
		
		return $result;
	}	
	
	function save($data){
		$feedId = (int)element('feedId', $data);
		
		if (trim($data['feedUrl']) == '') {
			return null;
		}
		
		$values = array(
			'feedUrl' 			=> $data['feedUrl'], 
			'statusId' 			=> FEED_STATUS_PENDING,
			'countryId'			=> element('countryId', $data),
			'langId'			=> element('langId', $data),
			'feedSuggest' 		=> element('feedSuggest', $data),
			'fixLocale' 		=> element('fixLocale', $data),
		);
		
		if (isset($data['feedName'])) {
			$values['feedName'] = $data['feedName'];
		}
		if (isset($data['feedLink'])) {
			$values['feedLink']	= $data['feedLink'];
		}
		if (isset($data['feedDescription'])) {
			$values['feedDescription']	= $data['feedDescription'];
		}

		$query = $this->db->where('feedUrl', $values['feedUrl'])->get('feeds')->result_array();
		//pr($this->db->last_query());
		if (!empty($query)) {
			$feedId = $query[0]['feedId'];
			
			if ((string)$query[0]['feedName'] == '') {
				$values['feedName'] = element('feedLink', $values);
			}
		}
		
		if ((int)$feedId != 0) {
			$this->db->update('feeds', $values, array('feedId' => $feedId));
		}
		else {
			$this->db->insert('feeds', $values);
			$feedId = $this->db->insert_id();
		}
		//pr($this->db->last_query());

		return $feedId;
	}
	
	function delete($feedId) {
		$this->db->delete('feeds', array('feedId' => $feedId));
		return true;
	}
	
	function search($filter){
		$filter = $this->db->escape_like_str($filter);
		
		return $this->db
			->select('DISTINCT feedId AS id, feedName AS text  ', false)
//			->where('statusId', STATUS_ACTIVE)
			->like('feedName', $filter)
			->order_by('text')
			->get('feeds', AUTOCOMPLETE_SIZE)->result_array();
	}	
	
	
	/**
	 * Busca tags que tengan feeds. 
	 */
	function searchTags($filter){
		$filter = $this->db->escape_like_str($filter);

		return $this->db
			->select('DISTINCT tags.tagId AS id, tagName AS text  ', false)
			->join('feeds_tags', 'feeds_tags.tagId = tags.tagId ', 'inner')
			->like('tagName', $filter)
			->order_by('text')
			->get('tags', AUTOCOMPLETE_SIZE)->result_array();
	}	
	
	function saveFeedIcon($feedId, $feed = null, $force = false) {
		if ($force == true) {
			$this->db->update('feeds', array( 'feedIcon' => null), array('feedId' => $feedId) );	
		}
		
		if ($feed == null) {
			$feed = $this->get($feedId);
			$this->load->model('Entries_Model');
		}
		$feedLink = $feed['feedLink'];
		$feedIcon = $feed['feedIcon'];
		
		if (trim($feedLink) != '' && $feedIcon == null) {
			$this->load->spark('curl/1.2.1');
			$img 			= $this->curl->simple_get('https://plus.google.com/_/favicon?domain='.$feedLink);
			$parse 			= parse_url($feedLink);
			$feedIcon 		= $parse['host'].'.png'; 
			file_put_contents('./assets/favicons/'.$feedIcon, $img);
			$this->db->update('feeds', array('feedIcon' => $feedIcon), array('feedId' => $feedId));	
		}

		return true;				
	}
	
	function updateFeedStatus($feedId, $statusId) {
		$this->db->update('feeds', array('statusId' => $statusId), array('feedId' => $feedId));
		//pr($this->db->last_query());		
	}

	function resetFeed($feedId) { // Reseteo las propiedades del feed para reescanear
		$this->db->update('feeds', 
			array(
				'feedLastScan' 			=> null,
				'feedLastEntryDate'		=> null, 
				'statusId' 				=> 0,
				'feedMaxRetries'		=> 0,
			),
			array('feedId' => $feedId)
		);
	}

	function scanFeed($feedId) {
		set_time_limit(0);
		
		$this->load->model('Entries_Model');
	
//sleep(5);

		// vuelvo a preguntar si es momento de volver a scanner el feed, ya que pude haber sido scaneado recién al realizar multiples peticiones asyncronicas
		$query = $this->db
			->select('feedLastEntryDate, feedUrl, fixLocale, feedMaxRetries, feedLink, feedIcon, TIMESTAMPDIFF(MINUTE, feedLastScan, DATE_ADD(NOW(), INTERVAL -'.FEED_TIME_SCAN.' MINUTE)) AS minutes ', false)
			->where('feeds.feedId', $feedId)
			->get('feeds')->result_array();
		//pr($this->db->last_query());  die;
		$feed = $query[0];
		if ($feed['minutes'] != null && (int)$feed['minutes'] < FEED_TIME_SCAN ) {  // si paso poco tiempo salgo, porque acaba de escanear el mismo feed otro proceso
			return;
		}
		
		$feedUrl		= $feed['feedUrl']; 
		$fixLocale		= $feed['fixLocale'];
		$feedMaxRetries = $feed['feedMaxRetries'];

		$this->load->spark('ci-simplepie/1.0.1/');
		$this->cisimplepie->set_feed_url($feedUrl);
		$this->cisimplepie->enable_cache(false);
		$this->cisimplepie->init();
		$this->cisimplepie->handle_content_type();

		if ($this->cisimplepie->error() ) {
			$this->db->update('feeds', 
				array(
					'feedMaxRetries'	=> $feedMaxRetries + 1,
					'statusId'			=> FEED_STATUS_PENDING,
					'feedLastScan'		=> date("Y-m-d H:i:s"),
					'feedLastEntryDate'	=> $this->Entries_Model->getLastEntryDate($feedId),
				),
				array('feedId' => $feedId)
			);
			if (($feedMaxRetries + 1) >= FEED_MAX_RETRIES) {
				$this->updateFeedStatus($feedId, FEED_STATUS_NOT_FOUND);
			}
			return;
		}

		$lastEntryDate = $feed['feedLastEntryDate'];
		
		$langId		= null;
		$countryId 	= null;
		if ($fixLocale == false) {
			$langId 	= strtolower($this->cisimplepie->get_language());
			$aLocale 	= explode('-', $langId);
			if (count($aLocale) == 2) {
				$countryId 	= strtolower($aLocale[1]);
			}
		}

		$rss = $this->cisimplepie->get_items(0, 50); // TODO: meter en una constante!

		foreach ($rss as $item) { 
			$aTags = array();
			if ($categories = $item->get_categories()) {
				foreach ((array) $categories as $category) {
					if ($category->get_label() != '') {
						$aTags[] = $category->get_label();
					}
				}
			}
			unset($categories, $category);
			
			$entryAuthor = '';
			if ($author = $item->get_author()) {
				$entryAuthor = $author->get_name();
			}

			$data = array(
				'feedId' 		=> $feedId,
				'entryTitle'	=> $item->get_title(),
				'entryContent'	=> (string)$item->get_content(),
				'entryDate'		=> $item->get_date('Y-m-d H:i:s'),
				'entryUrl'		=> (string)$item->get_link(),
				'entryAuthor'	=> (string)$entryAuthor,
			);

			if ($data['entryDate'] == null) {
				$data['entryDate'] = date("Y-m-d H:i:s");
			}

			if ($data['entryDate'] == $lastEntryDate) { // si no hay nuevas entries salgo del metodo
				// TODO: revisar, si la entry no tiene fecha, estoy seteando la fecha actual del sistema; y en este caso nunca va a entrar a este IF y va a hacer queries al pedo
				$this->db->update('feeds', 
					array(
						'statusId' 			=> FEED_STATUS_APPROVED,
						'feedLastScan' 		=> date("Y-m-d H:i:s"),
						'feedMaxRetries'	=> 0,
					), 
					array('feedId' => $feedId)
				);
				return;
			}

			$this->Entries_Model->saveEntry($data, $aTags);
		}

		$values = array(
			'statusId'			=> FEED_STATUS_APPROVED,
			'feedLastScan' 		=> date("Y-m-d H:i:s"),
			'feedLastEntryDate' => $this->Entries_Model->getLastEntryDate($feedId),
			'feedMaxRetries'	=> 0,
		); 
		if (trim((string)$this->cisimplepie->get_title()) != '') {
			$values['feedName'] = (string)$this->cisimplepie->get_title(); 			
		}
		if (trim((string)$this->cisimplepie->get_description()) != '') {
			$values['feedDescription'] = (string)$this->cisimplepie->get_description();
		}
		if (trim((string)$this->cisimplepie->get_link()) != '') {
			$values['feedLink'] = (string)$this->cisimplepie->get_link();
		}
		if ($langId != null) {
			$values['langId'] = $langId;
		}
		if ($countryId != null) {
			$values['countryId'] = $countryId;
		}

		$this->db->update('feeds', $values, array('feedId' => $feedId));

		$this->saveFeedIcon($feedId, (element('feedLink', $feed) != '' ? $feed : null));
	}

	function countUsersByFeedId($feedId) {
		$query = ' SELECT COUNT(1) AS total 
			FROM users_feeds
			WHERE feedId 		= '.$feedId.' ';
		$query = $this->db->query($query)->result_array();
		//pr($this->db->last_query());
		return $query[0]['total'];
	}

	function countEntriesByFeedId($feedId) {
		$query = ' SELECT COUNT(1) AS total 
			FROM entries
			WHERE feedId 		= '.$feedId.' ';
		$query = $this->db->query($query)->result_array();
		//pr($this->db->last_query());
		return $query[0]['total'];
	}
	
	function countEntriesStarredByFeedId($feedId) {
		$query = ' SELECT COUNT(1) AS total 
			FROM users_entries
			WHERE 	feedId 	= '.$feedId.' 
			AND 	tagId	= '.TAG_STAR;
		$query = $this->db->query($query)->result_array();
		//pr($this->db->last_query());
		return $query[0]['total'];
	}
	
	
	function deleteOldEntries() {
		$query = $this->db
			->select('feedId, feedName  ')
			->get('feeds')
			->order_by('feedId')
			->result_array();
		foreach ($query as $row) {
			$affectedRows = $this->deleteOldEntriesByFeedId($row['feedId']);
			echo $row['feedName'].' ('.$row['feedId'].') - <span style="'.($affectedRows > 0 ? ' color: #FF0000; font-weight: bold;' : '').'"> affected rows: '.$affectedRows.'</span><br/>';
		}
	}
	
	function deleteOldEntriesByFeedId($feedId, $count = 0) {
		$feedId 			= (int)$feedId;
		$minEntriesKeep		= 50;
		$monthsTokeep		= 3;
		$limit				= 500;
		
		$query = '
			DELETE FROM entries
			WHERE feedId = '.$feedId.' 
			AND entryId NOT IN (
				SELECT entryId
				FROM ( 
						SELECT * 
						FROM 
							(
							SELECT * FROM entries 
							WHERE feedId = '.$feedId.' 
							ORDER BY entries.entryDate DESC LIMIT '.$minEntriesKeep.'
							) AS lastEntries
					UNION ALL
						SELECT *
						FROM entries 
						WHERE feedId = '.$feedId.'
						AND  entryDate > DATE_ADD(NOW(), INTERVAL -'.$monthsTokeep.' MONTH)
					UNION ALL
						SELECT entries.*
						FROM entries 
						WHERE feedId = '.$feedId.'
						AND entryId IN (SELECT entryId FROM users_entries WHERE tagId = '.TAG_STAR.' AND feedId = '.$feedId.') 
				) AS entries
			) 
			LIMIT '.$limit;
		$this->db->query($query);

		$affectedrRows = $this->db->affected_rows();
		$count += $affectedrRows;;
		if ($affectedrRows != 0) {
			sleep(1);
			return $this->deleteOldEntriesByFeedId($feedId, $count);
		}
		return $count;
	}






	function selectFeedsOPML($userId) {

		$query = $this->db->select('feeds.feedId, feedName, feedUrl, tags.tagId, tagName, feeds.feedLink ', false)
						->join('users_feeds', 'users_feeds.feedId = feeds.feedId', 'left')
						->join('users_feeds_tags', 'users_feeds_tags.feedId = feeds.feedId AND users_feeds_tags.userId = users_feeds.userId', 'left')
						->join('tags', 'users_feeds_tags.tagId = tags.tagId', 'left')
						->where('users_feeds.userId', $userId)
						->order_by('tagName IS NULL, tagName asc, feedName asc')
		 				->get('feeds')->result_array();
		//pr($this->db->last_query());
		return $query;
	}
}
