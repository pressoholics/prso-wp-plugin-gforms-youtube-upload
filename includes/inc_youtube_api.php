<?php
/**
 * General App Functions
 *
 * Contents:
 * 	** PRSO PLUGIN FRAMEWORK METHODS **
 *		__construct()		- Magic method construct
 *		admin_init()		- Helps to consolidate all plugin wide calls to Wordpress action hooks that must be added during 'admin_init'
 *		enqueue_scripts()	- Call all plugin wp_enqueue_script or wp_enqueue_style here
 *		add_actions()		- Add any calls to Wordpress add_action() here
 *		add_filters()		- Add any calls to Wordpress add_filter() here
 *
 *	** METHODS SPECIFIC TO THIS PLUGIN **
 *		
 *
 */
class PrsoGformsYoutubeApi extends PrsoGformsYoutubeFunctions {
	
	public function init_api( $validated_attachments ) {
		
		//Init the youtube api
		$this->init_youtube_api();
		
		//Process video uploads
		return $this->init_youtube_uploads( $validated_attachments );
		
	}
	
	public function get_video_url( $video_id = NULL ) {
		
		//Init vars
		$video_entry = NULL;
		
		if( isset($video_id) ) {
			
			//Init the youtube api
			$this->init_youtube_api();
			
			//Get data on video
			$video_entry = $this->data['YouTubeClass']->getVideoEntry( $video_id );
			
			return $video_entry->getVideoWatchPageUrl();
		}
		
	}
	
	
	
	private function init_youtube_api() {
		
		//Init vars
		$returned_video_data = array();
		
		//Cache path to zend library
		$this->data['zend_library_path'] = $this->plugin_includes . '/Zend';
		
		set_include_path($this->plugin_includes . PATH_SEPARATOR . get_include_path());
		
		//Setup YouTube account details
		$this->data['http_client_args'] = array(
			'username'	=>	'ben@benjaminmoody.com',
			'password'	=>	'uuxcxusrvsdhbubl'
		);
		
		//Cache developer key
		$this->data['developer_key'] = 'AI39si4rrk8OJFq745m2kpoFB5E7_7MDdLXo3LSEV4jX_YV4DjT4BcZmwr0X-nS0gnCuTjYYk5Ks6co1o7QRJ0bU6gmXZWqTSQ';
		
		//Initialize zend youtube api object
		if( !isset($this->data['YouTubeClass']) ) {
			$this->init_youtube_api_obj();
		}
		
	}
	
	private function init_youtube_uploads( $validated_attachments ) {
		
		//Upload videos to youtube and cache the resulting array of video id/data
		$returned_video_data = $this->youtube_api_process_videos( $validated_attachments );
		
		//Now cache the returned video id for each video uploaded
		$returned_video_data = $this->youtube_api_cache_video_id( $returned_video_data );
		
		return $returned_video_data;
		
	}
	
	private function init_youtube_api_obj() {
		
		//Init vars
		$zend_loader_path 	= $this->data['zend_library_path'] . '/Loader.php';
		$zend_youtube_class = 'Zend_Gdata_YouTube';
		$client_login_class = 'Zend_Gdata_ClientLogin';
		$authenticationUrl	= 'https://www.google.com/accounts/ClientLogin';
		$httpClient			= NULL;
		
		$app_args = array(
			'applicationId'	=>	'Gforms YouTube Uploader WP Plugin',
			'clientId'		=>	NULL
		);
		
		extract($app_args);
		
		extract($this->data['http_client_args']);
		
		//Require zend loader class
		if( file_exists($zend_loader_path) ) {
			
			require_once( $zend_loader_path );
			
			//Load core youtube api class
			Zend_Loader::loadClass( $zend_youtube_class );
			
			//Load youtube ClientLogin class
			Zend_Loader::loadClass( $client_login_class );
			
			$httpClient = Zend_Gdata_ClientLogin::getHttpClient(
				$username,
				$password,
				$service		=	'youtube',
				$client 		=	NULL,
				$source			=	'Gravity Forms YouTube Uploader Wordpress Plugin',
				$loginToken		=	NULL,
				$loginCaptcha	=	NULL,
				$authenticationUrl
			);
			
			//Cache instance of authenticated class
			$this->data['YouTubeClass'] = new Zend_Gdata_YouTube( $httpClient, $applicationId, $clientId, $this->data['developer_key'] );
			
		}
		
	}
	
	private function youtube_api_process_videos( $validated_attachments ) {
		
		//Loop each attachment video and try to upload each to youtube
		foreach( $validated_attachments as $field_id => $attachments ) {
			
			foreach( $attachments as $key => $attachment_data ) {
			
				$validated_attachments[$field_id][$key]['video_data'] = $this->youtube_api_upload_video( $attachment_data );
				
			}
			
		}
		
		return $validated_attachments;
	}
	
	private function youtube_api_upload_video( $attachment_data ) {
		
		//Init vars
		$file_type		= NULL;
		$path_info		= NULL;
		$myVideoEntry 	= NULL;
		$uploadUrl		= NULL;
		$filesource		= NULL;
		$newEntry		= NULL;
		$output			= NULL;
		
		//Check for required data
		if( isset($attachment_data['file_path'], $attachment_data['mime_type'], $attachment_data['title'], $attachment_data['description']) ) {
			
			// upload URI for the currently authenticated user
			$uploadUrl = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads';
			
			// create a new VideoEntry object
			$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry();
			
			//Get file path
			$file_path = $attachment_data['file_path'];
			
			//Get file type
			$file_type 	= $attachment_data['mime_type'];
			
			//Get file slug - filename plus ext
			$path_info	= pathinfo( $file_path );
			
			// create a new Zend_Gdata_App_MediaFileSource object
			$filesource = $this->data['YouTubeClass']->newMediaFileSource( $file_path );
			$filesource->setContentType( $file_type );
			
			// set slug header
			$filesource->setSlug( $path_info['basename'] );
			
			// add the filesource to the video entry
			$myVideoEntry->setMediaSource( $filesource );
			
			$myVideoEntry->setVideoTitle( $attachment_data['title'] );
			$myVideoEntry->setVideoDescription( $attachment_data['description'] );
			
			// The category must be a valid YouTube category!
			$myVideoEntry->setVideoCategory('Autos');
			
			//Set video upload as private
			$myVideoEntry->setVideoPrivate();
			
			// try to upload the video, catching a Zend_Gdata_App_HttpException, 
			// if available, or just a regular Zend_Gdata_App_Exception otherwise
			try {
			
			  $output = $this->data['YouTubeClass']->insertEntry($myVideoEntry, $uploadUrl, 'Zend_Gdata_YouTube_VideoEntry');
			  
			  
			} catch (Zend_Gdata_App_HttpException $httpException) {
			  	$output = $httpException->getRawResponseBody();
			} catch (Zend_Gdata_App_Exception $e) {
			    $output = $e->getMessage();
			}
			
		}
		
		return $output;
	}
	
	private function youtube_api_cache_video_id( $validated_attachments ) {
		
		//Init vars
		$YoutubeObj = NULL;
		
		//Loop each attachment video and try to cache the video id from youtube
		foreach( $validated_attachments as $field_id => $attachments ) {
			
			foreach( $attachments as $key => $attachment_data ) {
				
				if( isset($attachment_data['video_data']) && is_object($attachment_data['video_data']) ) {
					
					$YoutubeObj = $attachment_data['video_data'];
					
					if( method_exists($YoutubeObj, 'getVideoId') ) {
						
						//Get video id from youtube returned object
						$validated_attachments[$field_id][$key]['video_id'] = $YoutubeObj->getVideoId();
						
						//Unset youtube object from array
						unset( $validated_attachments[$field_id][$key]['video_data'] );
						
					}
					
				}
				
			}
			
		}
		
		return $validated_attachments;
	}
	
	private function printVideoEntry($videoEntry) {
	  // the videoEntry object contains many helper functions
	  // that access the underlying mediaGroup object
	  echo 'Video: ' . $videoEntry->getVideoTitle() . "\n";
	  echo 'Video ID: ' . $videoEntry->getVideoId() . "\n";
	  echo 'Updated: ' . $videoEntry->getUpdated() . "\n";
	  echo 'Description: ' . $videoEntry->getVideoDescription() . "\n";
	  echo 'Category: ' . $videoEntry->getVideoCategory() . "\n";
	  echo 'Tags: ' . implode(", ", $videoEntry->getVideoTags()) . "\n";
	  echo 'Watch page: ' . $videoEntry->getVideoWatchPageUrl() . "\n";
	  echo 'Flash Player Url: ' . $videoEntry->getFlashPlayerUrl() . "\n";
	  echo 'Duration: ' . $videoEntry->getVideoDuration() . "\n";
	  echo 'View count: ' . $videoEntry->getVideoViewCount() . "\n";
	  echo 'Rating: ' . $videoEntry->getVideoRatingInfo() . "\n";
	  echo 'Geo Location: ' . $videoEntry->getVideoGeoLocation() . "\n";
	  echo 'Recorded on: ' . $videoEntry->getVideoRecorded() . "\n";
	  
	  // see the paragraph above this function for more information on the 
	  // 'mediaGroup' object. in the following code, we use the mediaGroup
	  // object directly to retrieve its 'Mobile RSTP link' child
	  foreach ($videoEntry->mediaGroup->content as $content) {
	    if ($content->type === "video/3gpp") {
	      echo 'Mobile RTSP link: ' . $content->url . "\n";
	    }
	  }
	  
	  echo "Thumbnails:\n";
	  $videoThumbnails = $videoEntry->getVideoThumbnails();
	
	  foreach($videoThumbnails as $videoThumbnail) {
	    echo $videoThumbnail['time'] . ' - ' . $videoThumbnail['url'];
	    echo ' height=' . $videoThumbnail['height'];
	    echo ' width=' . $videoThumbnail['width'] . "\n";
	  }
	}
	
}