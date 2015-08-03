<?php

/**
*	Creation of the admin form.
*/
function brafton_admin_form2(){
    echo '<h1>hello</h1>';
}
function get_it(){
    $val = variable_get('brafton_temp_image');
    $val = unserialize($val);
    $string = '';
    foreach($val as $key => $value){
        $string .= $key .' with' .$value;
    }
    return $string;
}
//this function will get the erros and turn them into a readable string
function get_errs(){
    $ser = variable_get('brafton_e_log');
    $errors = unserialize($ser);
    $string = '';
    if(is_array($errors)){
            $errors = array_reverse($errors);
        for($i=0;$i<count($errors);++$i){
            $string .= $errors[$i]['client_sys_time'].':<br/>  ----'.$errors[$i]['error'].'<br/>';
        }
    } else{
        $string = 'There are no errors';
    }
    return $string;
}
function brafton_run_manaul_article($form, &$form_state){
    $dis_list = '<ul>';
     $import_list = BraftonArticleImporter();
    foreach($import_list['title'] as $key){
        $dis_list .= '<li>'.$key.'</li>';
    }
    $dis_list .= '</ul>';
    drupal_set_message(t('You have Run the Article Importer and Imported '.$import_list['counter'].' articles :<br/>'. $dis_list));
}
function brafton_run_video_importer($form, &$form_state){
    $dis_list = '<ul>';
    $import_list = BraftonVideoImporter();
    foreach($import_list as $key => $val){
        $dis_list .= '<li>'.$val['title'].'</li>';
    }
    $dis_list .= '</ul>';
     drupal_set_message(t('You have Run the Video Importer and Imported :<br/>'. $dis_list));
}
function brafton_admin_form_submit($form, &$form_state){
    //reset the error report
    if($form_state['values']['brafton_clear_report']){
        variable_set('brafton_e_log', '');
        //variable_set('brafton_clear_report', 0);
    }
    
    //runs importer if archive is loaded
    //Handles background image for videos
    if($form_state['values']['brafton_video_end_cta_background'] != ''){
        $file = file_load($form_state['values']['brafton_video_end_cta_background']);
      // Change status to permanent.
        $file->status = FILE_STATUS_PERMANENT;
      // Save.
        $newfile = file_save($file);
        $name = basename('brafton.module', '.module');
        file_usage_add($newfile, $name, $name, $newfile->fid);  
        variable_set('brafton_video_end_cta_background_url', $newfile->uri);
        variable_set('brafton_video_end_cta_background_id', $newfile->fid);
        
    } else if(!$form_state['values']['brafton_video_end_cta_background']['fid']){
        variable_set('brafton_video_end_cta_background_url','');
        variable_del('brafton_video_end_cta_background_id');
    }
    //Handles Button Image for videos
    if($form_state['values']['brafton_video_end_cta_button_image'] != ''){
        $file = file_load($form_state['values']['brafton_video_end_cta_button_image']);
      // Change status to permanent.
        $file->status = FILE_STATUS_PERMANENT;
      // Save.
        $newfile = file_save($file);
        $name = basename('brafton.module', '.module');
        file_usage_add($newfile, $name, $name, $newfile->fid);  
        variable_set('brafton_video_end_cta_button_image_url', $newfile->uri);
        variable_set('brafton_video_end_cta_button_image_id', $newfile->fid);
        
    } else if(!$form_state['values']['brafton_video_end_cta_button_image']['fid']){
        variable_set('brafton_video_end_cta_button_image_url','');
        variable_del('brafton_video_end_cta_button_image_id');
    }
    //Ensure that the run manual imports
    $form_state['values']['brafton_clear_report'] = 0;
}
function spit_image($image){
    $string = '';
    if(variable_get($image.'_url') != ''){
        $string = '<img src="'.file_create_url(variable_get($image.'_url')).'" class="background_preview '.$image.'">';
    }
    return $string;
}
function build_type_list($list){
    $array = array();
    foreach($list as $key){
        if($key['type'] == 'b_video'){ continue; }
        $array[$key['type']] = $key['name'];
    }
    return $array;
}
function build_type_field_list($type){
    
    $names = field_info_instances('node',$type);
    $test = array();
    foreach($names as $key){
        $test[$key['field_name']] = $key['field_name'];
    }
    return $test;
}
function ajax_brafton_test($form, &$form_state){
    return $form['brafton_article_options']['brafton_type_info'];
}
function brafton_admin_form($form, &$form_state)	{
    if(isset($_GET['b_error']) && $_GET['b_error'] == 'vital'){
        drupal_set_message(t('There was a fatal error when running the importer.  Please contact Tech support'));
    }
    $name = basename('brafton.module', '.module');
    /* This would be section to add new css rules for our admin page */
    drupal_add_css(drupal_get_path('module', $name).'/brafton-admin.css', array(
        'group' => CSS_THEME,
        'type' => 'file',
      ));
      
	$form = array();
    $types = node_type_get_types();
    $type_list = array();
    foreach($types as $obj){
        $type_list[] = array(
            'name' => $obj->name,
            'type' => $obj->type
            );
    }
	//Gets the users as an array for the author dropdown
	
	$results = db_query( "SELECT uid, name FROM {users} WHERE status=1" );
	$user_array = $results->fetchAllKeyed();
    
    //Add option for getting dynamic author.
    //0 is also the id for anonymous author as a fall back if no author is set in the feed
    $user_array[0] = 'Get Author from Article';
    
	//Renders the admin form using the Drupal forms API.
    /*
     *************************************************************************************
     * General Options
     *************************************************************************************
     */
    $form['brafton_general_options'] = array(
        '#type' => 'fieldset',
        '#title'    => 'General Options',
        '#collapsible'  => true,
        '#collapsed'    => true
    );
	$form['brafton_general_options']['brafton_feed_type'] = array(
		'#type' => 'select',
		'#title' => t( 'Type of Content' ),
		'#description' => t( 'The type(s) of content you are importing.' ),
		'#options' => array(
			'articles' => 'Articles',
			'videos' => 'Videos',
			'both' => 'Both',
		),
		'#default_value' => variable_get( 'brafton_feed_type','articles' ),
		'#prefix' => '<h2>Choose Content Types</h2>',
	);
	$form['brafton_general_options']['brafton_api_root'] = array(
		'#type' => 'select',
		'#title' => t( 'API Root' ),
		'#description' => t( 'The root domain of your Api key (i.e, api.brafton.com).' ),
		'#options' => array(
			'http://api.brafton.com' => 'Brafton',
			'http://api.contentlead.com' => 'ContentLEAD',
			'http://api.castleford.com.au' => 'Castleford',
		),
		'#default_value' => variable_get( 'brafton_api_root','http://api.brafton.com' )
	);
    $form['brafton_general_options']['brafton_author'] = array(
		'#type' => 'select',
		'#title' => t( 'Content Author' ),
		'#description' => t( 'The author of the content.' ),
		'#options' => $user_array,
		'#default_value' => variable_get( 'brafton_author',1 ),
		'#prefix' => '<h2>Import Options</h2>',
	);
	$form['brafton_general_options']['brafton_import_date'] = array(
		'#type' => 'select',
		'#title' => t( 'Import Date' ),
		'#description' => t( 'The date that the content is marked as having been published.' ),
		'#options' => array(
			'published' => 'Published Date',
			'created' => 'Created Date',
			'lastmodified' => 'Last Modified Date',
		),
		'#default_value' => variable_get( 'brafton_import_date','published' ),
	);
	$form['brafton_general_options']['brafton_comments'] = array(
		'#type' => 'select',
		'#title' => t( 'Enable Comments?' ),
		'#description' => t( 'Enable, Hide, or Disable Comments' ),
		'#options' => array(
			0 => 'Disabled',
			1 => 'Hidden',
			2 => 'Enabled',
		),
		'#default_value' => variable_get( 'brafton_comments',0 ),
	);
	$form['brafton_general_options']['brafton_overwrite'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Overwrite any changes made to existing content.' ),
		'#default_value' => variable_get( 'brafton_overwrite',0 ),
	);
    /*
     *************************************************************************************
     * Article Options
     *************************************************************************************
     */
    $form['brafton_article_options'] = array(
        '#type' => 'fieldset',
        '#title'    => 'Article Options Options',
        '#collapsible'  => true,
        '#collapsed'    => true
    );
	$form['brafton_article_options']['brafton_api_key'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Api Key' ),
		'#description' => t( 'Your API key (of the format xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx).' ),
		'#default_value' => variable_get( 'brafton_api_key' ),
		'#size' => 36,
		'#maxlength' => 36,
        '#prefix'   => 'Options in this section apply to Articles ONLY.  Videos have seperate options'
	);
    $form['brafton_article_options']['brafton_blog_page'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a News Page at "mydomain.com/news".' ),
		'#default_value' => variable_get( 'brafton_blog_page',0 ),
		'#prefix' => '<h2>Integration Options for Articles</h2>',
	);
	$form['brafton_article_options']['brafton_blog_archives'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create archives pages at "mydomain.com/news/archive/year/month" and an archives block.' ),
		'#default_value' => variable_get( 'brafton_blog_archives',0 ),
	);
	$form['brafton_article_options']['brafton_blog_categories'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a categories block.' ),
		'#default_value' => variable_get( 'brafton_blog_categories',0 ),
	);
	$form['brafton_article_options']['brafton_blog_headlines'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a headlines block.' ),
		'#default_value' => variable_get( 'brafton_blog_headlines',0 ),
	);
	$form['brafton_article_options']['brafton_related_articles'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Add related articles to Brafton posts.' ),
		'#default_value' => variable_get( 'brafton_related_articles',0 ),
	);
    $form['brafton_article_options']['brafton_published'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Import articles as unpublished.' ),
		'#default_value' => variable_get( 'brafton_published',0 ),
	
	);
    $form['brafton_article_options']['brafton_existing_type'] = array(
        '#type' => 'select',
        '#title'    => 'Use Existing Type',
        '#options'  => build_type_list($type_list), 
        '#default_value'    => variable_get('brafton_existing_type', 'b_news'),
        '#description'  => 'Import Articles under an existing content type<span class="disclaimer">ADVANCED OPTION.  Only change if you know what you are doing</span>',
        '#ajax' => array(
            'callback'  => 'ajax_brafton_test',
            'wrapper'   => 'brafton_type_info_block',
            'replace'   => 'replace',
            'effect'    => 'fade'
            ),
    );
    $form['brafton_article_options']['brafton_type_info'] = array(
        '#title'    => '',
        '#prefix'   => '<div id="brafton_type_info_block">',
        '#suffix'   => '</div>',
        '#type' => 'fieldset'
    );
    //displays the form with values for existing post type on initial page load
    if(empty($form_state['values']['brafton_existing_type']) && variable_get('brafton_existing_type') != 'b_news'){
    $form['brafton_article_options']['brafton_type_info']['brafton_custom_body'] = array(
            '#type' => 'select',
            '#prefix'   => '<p>Map your content fields to the appropriate content parts.<span class="disclaimer">Caution the following are advanced options and should only be used if you know what your are doing</span></p>',
            '#title'    => 'Content of the Article',
            '#options'  => build_type_field_list(variable_get('brafton_existing_type')),
            '#default_value'    => variable_get('brafton_custom_body', '')
        );
        $form['brafton_article_options']['brafton_type_info']['brafton_custom_image'] = array(
            '#type' => 'select',
            '#title'    => 'Image for Article',
            '#options'  => build_type_field_list(variable_get('brafton_existing_type')),
            '#default_value'    => variable_get('brafton_custom_image', '')
        );
        $form['brafton_article_options']['brafton_type_info']['brafton_custom_taxonomy'] = array(
            '#type' => 'select',
            '#title'    => 'Taxonomy for Article',
            '#options'  => build_type_field_list(variable_get('brafton_existing_type')),
            '#default_value'    => variable_get('brafton_custom_taxonomy', '')
        );
    }
    //used for displaying the ajax load of content type to display the fields available for mapping
    if( (!empty($form_state['values']['brafton_existing_type']) && $form_state['values']['brafton_existing_type'] != 'b_news')){
        $current = $form_state['values']['brafton_existing_type'];
         $form['brafton_article_options']['brafton_type_info']['brafton_custom_body'] = array(
            '#type' => 'select',
            '#prefix'   => '<p>Map your content fields to the appropriate content parts.<span class="disclaimer">Caution the following are advanced options and should only be used if you know what your are doing</span></p>',
            '#title'    => 'Content of the Article',
            '#options'  => build_type_field_list($current),
            '#default_value'    => ''
        );
        $form['brafton_article_options']['brafton_type_info']['brafton_custom_image'] = array(
            '#type' => 'select',
            '#title'    => 'Image for Article',
            '#options'  => build_type_field_list($current),
            '#default_value'    => ''
        );
        $form['brafton_article_options']['brafton_type_info']['brafton_custom_taxonomy'] = array(
            '#type' => 'select',
            '#title'    => 'Taxonomy for Article',
            '#options'  => build_type_field_list($current),
            '#default_value'    => ''
        );
    }
    /*
     *************************************************************************************
     * Video Options
     *************************************************************************************
     */
    $form['brafton_video_options'] = array(
        '#type' => 'fieldset',
        '#title'    => 'Video Options Options',
        '#collapsible'  => true,
        '#collapsed'    => true
    );
	$form['brafton_video_options']['brafton_video_public_key'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Video Public Key' ),
		'#description' => t( 'Your video Public Key.' ),
		'#default_value' => variable_get( 'brafton_video_public_key' ),
		'#size' => 8,
		'#maxlength' => 8,
		'#prefix' => '<h2>For Videos</h2>',
	);
	$form['brafton_video_options']['brafton_video_secret_key'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Video Secret Key' ),
		'#description' => t( 'Your video Secret Key.' ),
		'#default_value' => variable_get( 'brafton_video_secret_key' ),
		'#size' => 36,
		'#maxlength' => 36,
	);
	$form['brafton_video_options']['brafton_video_feednum'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Video Feed Number' ),
		'#description' => t( 'Your video feed number.' ),
		'#default_value' => variable_get( 'brafton_video_feednum',0 ),
		'#size' => 10,
		'#maxlength' => 10,
	);
    
	$form['brafton_video_options']['brafton_video_page'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a Video Page at "mydomain.com/video".' ),
		'#default_value' => variable_get( 'brafton_video_page',0 ),
		'#prefix' => '<h2>Integration Options for Videos</h2>',
	);
	$form['brafton_video_options']['brafton_video_archives'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create video archives pages at "mydomain.com/video/archive/year/month" and a video archives block.' ),
		'#default_value' => variable_get( 'brafton_video_archives',0 ),
	);
	$form['brafton_video_options']['brafton_video_categories'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a video categories block.' ),
		'#default_value' => variable_get( 'brafton_video_categories',0 ),
	);
	$form['brafton_video_options']['brafton_video_headlines'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Create a video headlines block.' ),
		'#default_value' => variable_get( 'brafton_video_headlines',0 ),
	);
	$form['brafton_video_options']['brafton_related_videos'] = array(
		'#type' => 'checkbox',
		'#title' => t( 'Add related videos to Brafton videos.' ),
		'#default_value' => variable_get( 'brafton_related_videos',"" ),
	);
    $form['b_cta'] = array(
        '#type' => 'fieldset',
        '#title'    => 'Brafton Video CTA\'s',
        '#collapsible'  => true,
        '#collapsed'    => true
    );
    $form['b_cta']['brafton_video_ctas'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Video CTA\'s'),
        '#default_value'    => variable_get('brafton_video_ctas')
    );
	$form['b_cta']['brafton_video_pause_cta_text'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Atlantis Pause CTA Text' ),
		'#description' => t( 'Default video pause cta text every article imports' ),
		'#default_value' => variable_get( 'brafton_video_pause_cta_text',"" ),
		'#size' => 20,
		'#maxlength' => 20,
        '#prefix'   => variable_get('testingName')
	);
    $form['b_cta']['brafton_video_pause_cta_link'] = array(
        '#type' => 'textfield',
        '#title'    => t('Atlantis Pause Link'),
        '#description'  => t('Default video pause cta link'),
        '#default_value'   => variable_get('brafton_video_pause_cta_link'),
        '#size' => 20
    );
    $form['b_cta']['brafton_video_pause_cta_asset_gateway_id'] = array(
        '#type' => 'textfield',
        '#title'    => t('Pause Asset Gateway ID'),
        '#description'  => t('Asset Gateay Form ID. disables pause link url'),
        '#default_value'   => variable_get('brafton_video_pause_cta_asset_gateway_id'),
        '#size' => 20
    );
	$form['b_cta']['brafton_video_end_cta_title'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Atlantis End CTA Title' ),
		'#description' => t( 'Default video end cta title every article imports' ),
		'#default_value' => variable_get( 'brafton_video_end_cta_title',"" ),
		'#size' => 20,
		'#maxlength' => 140,
	);
	$form['b_cta']['brafton_video_end_cta_subtitle'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Atlantis End CTA Subtitle' ),
		'#description' => t( 'Default video end cta subtitle every article imports' ),
		'#default_value' => variable_get( 'brafton_video_end_cta_subtitle',"" ),
		'#size' => 20,
		'#maxlength' => 140,
	);
	$form['b_cta']['brafton_video_end_cta_link'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Atlantis End CTA Link' ),
		'#description' => t( 'Default video end cta link every article imports. Requires http://' ),
		'#default_value' => variable_get( 'brafton_video_end_cta_link',"" ),
		'#size' => 20,
		'#maxlength' => 500,
	);
    $form['b_cta']['brafton_video_end_cta_asset_gateway_id'] = array(
        '#type' => 'textfield',
        '#title'    => t('End Asset Gateway ID'),
        '#description'  => t('Asset Gateay Form ID. disables end link url'),
        '#default_value'   => variable_get('brafton_video_end_cta_asset_gateway_id'),
        '#size' => 20
    );
	$form['b_cta']['brafton_video_end_cta_text'] = array(
		'#type' => 'textfield',
		'#title' => t( 'Atlantis End CTA Text' ),
		'#description' => t( 'Default video end cta text every article imports' ),
		'#default_value' => variable_get( 'brafton_video_end_cta_text',"" ),
		'#size' => 20,
		'#maxlength' => 20,
	);
    $form['b_cta']['brafton_video_end_cta_button_image'] = array(
        '#type' => 'managed_file',
		'#title' => t( 'Ending CTA Button Image' ),
		'#description' => '<span class="actual_description">This is Optional</span>'.spit_image('brafton_video_end_cta_button_image'),
        '#upload_location'  => 'public://',
        '#default_value'    => variable_get('brafton_video_end_cta_button_image'),
    );
    $form['b_cta']['brafton_video_end_cta_background'] = array(
        '#type' => 'managed_file',
		'#title' => t( 'Ending Background Image' ),
		'#description' => '<span class="actual_description">This is Optional</span>'.spit_image('brafton_video_end_cta_background'),
        '#upload_location'  => 'public://',
        '#default_value'    => variable_get('brafton_video_end_cta_background'),
    );
    $form['b_error'] = array(
        '#type' => 'fieldset',
        '#title'    => 'Brafton Error Reporting',
        '#collapsible'  => true,
        '#collapsed'    => true
        );
    $form['b_error']['brafton_error_logs'] = array(
        '#type' => 'radio',
        '#title'    => t('Report Log'),
        '#description'  => get_errs(),
        '#format'   => 'full_html',
        '#default_value'    => variable_get('brafton_error_logs',0),
        '#prefix'   => '<h3>Error Report</h3>'
    );
    $form['b_error']['brafton_clear_report'] = array(
        '#type' => 'checkbox',
		'#title' => t( 'Clear the Error Report Log' ),
		'#default_value' => variable_get( 'brafton_clear_report',0 )
    );
    $form['b_manual'] = array(
        '#type' => 'fieldset',
        '#title'    => 'Manual Control & Archive Uploads',
        '#collapsible'  => true,
        '#collapsed'    => true
    );
    /*
    $form['b_manual']['brafton_article'] = array(
        '#type' => 'fieldset',

    );
    */
    /*
    $form['b_manual']['brafton_article']['brafton_enable_article_import'] = array(
        '#type' => 'checkbox',
		'#title' => t( 'Run Article Importer' ),
		'#default_value' => variable_get( 'brafton_enable_article_import',0 ),
        '#prefix'   => '<h2>Run Article Importer</h2>'
    );
    */
    $form['b_manual']['brafton_run_importer'] = array(
        '#type' => 'submit',
        '#title'    => 'Run Article Importer',
        '#value'    => 'Run Article Importer',
        '#submit'   => array('brafton_run_manaul_article')
    );
    $form['b_manual']['brafton_run_video_importer'] = array(
        '#type' => 'submit',
        '#title'    => 'Run Video Importer',
        '#value'    => 'Run Video Importer',
        '#submit'   => array('brafton_run_video_importer')
    );
    if(variable_get('brafton_archive_file')){
        $form['b_manual']['brafton_run_archive_importer'] = array(
            '#type'     => 'submit',
            '#title'    => 'Run Archive Importer',
            '#value'    => 'Run Archive Importer',
            '#submit'   => array('brafton_run_manaul_article')
        );
    }
    $form['b_manual']['brafton_archive_file'] = array(
		'#type' => 'managed_file',
		'#title' => t( 'Article Archive File' ),
		'#description' => t( 'When using the Archive import feature you must click upload and than Save configuration.  Once you Save your configuration you may run the Archive Importer which will use the uploaded xml file.' ),
		'#default_value' => variable_get( 'brafton_archive_file' ),
		'#upload_validators' => array(
			'file_validate_extensions' => array(0 => 'xml'),
		),
	);
    $form['#submit'][] = 'brafton_admin_form_submit';
	return system_settings_form($form);

}
