<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Foursquare_controller extends CI_Controller {
	
	public $layout = 'default';
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		
		// Require user access, but not for CLI work
		if (!$this->session->userdata('user') && !$this->input->is_cli_request())
			redirect('/login/?redirect=/foursquare');

		$this->load->helper('time');
		$this->load->helper('tag');

		$this->apiKey = $this->config->item('foursquare_consumer_key');
		$this->clientId = $this->config->item('foursquare_consumer_key');
		$this->clientSecret = $this->config->item('foursquare_consumer_secret');
		$this->callbackUrl = $this->config->item('foursquare_callback_url');
		
		$this->load->library('IgniteFoursquare', array('clientId' => $this->clientId, 'clientSecret' => $this->clientSecret, 'redirectUrl' => $this->callbackUrl));
		
		// Tokens model
		$this->load->model('foursquare_token');
		$this->load->model('foursquare_check');

		// If set in GET parameters, set and write to cookie
		if ($this->input->get('start_date') && $this->input->get('end_date')):
			$this->date_range = array(
				'start_date' => date('c', strtotime($this->input->get('start_date')) ),
				'end_date' => date('c', strtotime($this->input->get('end_date')) )
			);
			$this->input->set_cookie('locmon_start_date', $this->input->get('start_date'), 3600, null, '/');
			$this->input->set_cookie('locmon_end_date', $this->input->get('end_date'), 3600, null, '/');
		// Read from cookie
		elseif ($this->input->cookie('locmon_start_date') && $this->input->cookie('locmon_end_date')):
			$this->date_range = array(
				'start_date' => date('c', strtotime($this->input->cookie('locmon_start_date')) ),
				'end_date' => date('c', strtotime($this->input->cookie('locmon_end_date')) )
			);
		// Default to 7 days back
		else:
			$this->date_range = array(
				'start_date' => date('c', time() - (3600*24*7) ),
				'end_date' => date('c', time())
			);
		endif;

	}
	
	/**
	 * Index
	 */
	public function index() {

		// Redirect to Dashboard
		redirect('dashboard');
		
	}

	/**
	 * Venue
	 */
	public function venue() {
		
		// Setup for various actions
		$data = (array) $this->setup();
		
		// Authenticated User
		$user = unserialize($this->session->userdata('user'));
		
		// Grab venue from URL string
		$venue_id = $this->uri->segment(3);
		if (!$venue_id)
			show_error('Could not find venue.', 404);
		
		// Basic venue data
		$venue = json_decode($this->ignitefoursquare->GetPrivate(sprintf('/venues/%s', $venue_id)));
		if (isset($venue->meta->code) && $venue->meta->code != 200)
			show_error($venue->meta->errorDetail, $venue->meta->code);
		$data['venue'] = $venue->response->venue;

		// Tips
		$tips = json_decode($this->ignitefoursquare->GetPrivate(sprintf('/venues/%s/tips', $venue_id)));
		if (isset($tips->meta->code) && $tips->meta->code != 200)
			show_error($tips->meta->errorDetail, $tips->meta->code);
		$data['tips'] = $tips->response->tips;
		
		// Photos
		$photos = json_decode($this->ignitefoursquare->GetPrivate(sprintf('/venues/%s/photos', $venue_id)));
		if (isset($photos->meta->code) && $photos->meta->code != 200)
			show_error($photos->meta->errorDetail, $photos->meta->code);
		$data['photos'] = $photos->response->photos;
		
		// Nearby
		$position = number_format($venue->response->venue->location->lat,6,'.',',') . ',' . number_format($venue->response->venue->location->lng,6,'.',',');
		$nearby = json_decode($this->ignitefoursquare->GetPrivate('/venues/search', array('ll' => $position, 'limit' => 10)));
		if (isset($nearby->meta->code) && $nearby->meta->code != 200)
			show_error($nearby->meta->errorDetail, $nearby->meta->code);
		$data['nearby'] = $nearby->response->venues;

		// Get list of Foursquare Checks
		$checks = $this->foursquare_check->getChecksByUserId($user->id);
		$data['checks'] = $checks;

		$tags = $this->foursquare_check->getTags();
		$data['tags'] = $tags;

		// Check Data
		$check = $this->foursquare_check->getCheckByVenueId($venue_id);
		if (isset($check->id) && $check->id > 0):
			
			// Check Settings
			$data['check'] = $check;

			$this->load->model('foursquare_check_log_live');
			$live_data = $this->foursquare_check_log_live->getCheckData($check->id, $this->date_range);
			$data['live_data'] = $live_data;

			$this->load->model('foursquare_check_log');
			$daily_data = $this->foursquare_check_log->getCheckData($check->id, $this->date_range);
			$data['daily_data'] = $daily_data;
		
			// Calculate date difference
			$data['daily_data_delta'] = $this->foursquare_check_log->deltaArray($daily_data, 'log_date');
		
		endif;

		$data['date_range'] = $this->date_range;

		$data['page_title'] = sprintf('Venue: %s', isset($data['venue']->name) ? __($data['venue']->name) : 'Venue');
		$data['head_content'] = $this->load->view('foursquare/_head', $data, true);
		$data['head_content'] .= $this->load->view('checks/_chart_js', $data, true);
		$data['sidebar_content'] = $this->load->view('checks/_sidebar', $data, true);
		$data['sidebar_content'] .= $this->load->view('checks/_date_range_form', $data, true);

		$this->load->view('foursquare/venue', $data);
		$this->load->view('checks/_check_modal_js', $data);
		
	}

	/**
	 * Profile
	 */
	public function profile() {
		
		// Setup for various actions
		$data = (array) $this->setup();
		
		// Grab venue from URL string
		$profile_id = $this->uri->segment(3);
		if (!$profile_id)
			show_error('Could not find user.', 404);
		
		$profile = json_decode($this->ignitefoursquare->GetPrivate(sprintf('/users/%s', $profile_id)));
		$data['member'] = $profile->response->user;

		$data['page_title'] = sprintf('Profile: %s %s', isset($data['member']->firstName) ? __($data['member']->firstName) : '', isset($data['member']->lastName) ? __($data['member']->lastName) : '' );
		$this->load->view('foursquare/profile', $data);
		
	}

	/**
	 * Search
	 */
	public function search() {
		// Setup for various actions
		$data = (array) $this->setup();
		
		// Authenticated User
		$user = unserialize($this->session->userdata('user'));
		
		// Get list of Foursquare Checks
		$checks = $this->foursquare_check->getChecksByUserId($user->id);
		$data['checks'] = $checks;

		// Re-assign check array to be keyed by venue ID
		$checks_by_venue = array();
		foreach ($checks as $check_item):
			$checks_by_venue[$check_item->venue_id] = $check_item;
		endforeach;
		$data['checks_by_venue'] = $checks_by_venue;
		
		// Get search results
		if ($this->input->get('near') != '' && $this->input->get('q')):
				
				$query = $this->input->get('q');
				$near = $this->input->get('near');
				
				// Process query results
				$position = $this->_geocode($near);
				
				if ($position == false)
					show_error('Search failed to locate a suitable address');
				
				$ll = join(',', array($position->location->lat, $position->location->lng));
				
				$search_results = json_decode($this->ignitefoursquare->GetPrivate('/venues/search', array('ll' => $ll, 'query' => $query, 'limit' => 10)));
				$data['search_results'] = $search_results->response->venues;
		 
		else:
			$data['search_results'] = null;
		endif;
		
		$data['check'] = $this->foursquare_check->getInstance();
		
		$data['page_title'] = 'Search Venues';
		$data['sidebar_content'] = $this->load->view('checks/_sidebar', $data, true);
		$this->load->view('foursquare/search', $data);
		$this->load->view('checks/_check_modal_js', $data);
		
	}
	
	/**
	 * Setup
	 */
	private function setup() {
		$user = unserialize($this->session->userdata('user'));
		$user_token = $this->foursquare_token->getTokenByUserId($user->id);

		if (is_array($user_token)) {
			$token = $user_token;
			$this->ignitefoursquare->SetAccessToken($token[0]);
			$this->ignitefoursquare->setAuthenticated(true);
			$data['foursquareAuthenticated'] = $this->ignitefoursquare->isAuthenticated();
			
		} else {
			redirect('foursquare/authenticate');
		}
		
		// Logged in user info
		$userInfo = json_decode($this->ignitefoursquare->GetPrivate('/users/self'));

		// Check for error in initial response, usually indicates API outage
		if (!isset($userInfo->response->user) && isset($userInfo->reponse->errorDetail))
			show_error($userInfo->response->errorDetail, 500);
		elseif ($userInfo == false)
			show_error('Application could not understand response from the foursquare server.', 500);

		$data['userInfo'] = $userInfo->response->user;

		return $data;
		
	}


	/* *** OAuth Related *** */

	/**
	 * Authenticate
	 */
	public function authenticate() {
		// Authenticate if not already
		$authorizeUrl = $this->ignitefoursquare->AuthenticationLink($this->ignitefoursquare->redirectUrl);
		redirect($authorizeUrl);
	}
	
	/**
	 * Callback
	 */
	public function callback() {
		if ($this->input->get('code') != '') {
			$user = unserialize($this->session->userdata('user'));
			$token = $this->ignitefoursquare->GetToken($this->input->get('code'), $this->ignitefoursquare->redirectUrl);
			$this->foursquare_token->saveUserToken($user->id, array($token));
			$this->ignitefoursquare->SetAccessToken($token);
			$this->ignitefoursquare->setAuthenticated(true);
		} else {
			$this->session->set_flashdata('message', 'The callback received was invalid: ' . $this->input->get('error'));
			redirect('');
		}
		redirect('');
	}
	
	/**
	 * Reset
	 */
	public function reset() {
		$user = unserialize($this->session->userdata('user'));
		$this->foursquare_token->deleteUserToken($user->id);
		redirect('');
	}
	
	/* *** Private Methods *** */



	private function _geocode($address = null) {

		// Set up request to Google
		$headers = array();
		//$headers[] = "Authorization: Bearer " . $this->oauthToken;

		// Process cURL results
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, sprintf('http://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false', urlencode($address)) );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		
		// Check Response
		$json = json_decode($output);
		if ($json->status != 'OK')
			return false;

		// Return only the relevent component
		return $json->results[0]->geometry;
		
	}

}