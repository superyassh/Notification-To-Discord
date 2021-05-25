<?php
/*
 * Plugin Name: Notification to Discord
 * Plugin URI: https://github.com/superyassh
 * Description: Automatically sends Notification to Discord Via Webhook URL
 * Version: 2.0.0
 * Author: Superyassh
 * Author twitter: @superyassh
*/
 
function notification_to_discord($new_status, $old_status, $post) { 
  if(get_option('discord_webhook_url') == null) 
      return;
    
  if ( $new_status != 'publish' || $old_status == 'publish' || $post->post_type != 'post')
      return;

  $webhookURL = get_option('discord_webhook_url');
  $id = $post->ID;
          $excerpt = sanitize_text_field(
              html_entity_decode(
                  (!empty($post->post_excerpt) ? $post->post_excerpt : $post->post_content)
              )
          );
  $drole = get_the_terms($postID, 'discord-role');
  $role = isset($drole[0]) ? mb_strtolower($drole[0]->slug) : $postTypeName;
  $postTitle = $post->post_title;
  $permalink = get_permalink($id);
  $excerpt = trim(substr($excerpt, 0, 512)) . (strlen($excerpt) > 512 ? '[...]' : '');
  $thumbnail = get_the_post_thumbnail_url($postID, 'thumbnail');
  $image = get_the_post_thumbnail_url($postID, 'large');
              

  $message = " <@&$role> " ." **$postTitle** \r\n". "$permalink";

  $embed = json_encode([
    // Message
    "content" => $message,

    // Embeds Array
    "embeds" => [
        [
            // Embed Title
            "title" => "$postTitle",

            // Embed Type
            "type" => "rich",

            // Embed Description
            "description" => "$excerpt",

            // URL of title link
            "url" => "$permalink",

            // Timestamp of embed must be formatted as ISO8601
            //"timestamp" => $timestamp,

            // Embed left border color in HEX
            "color" => hexdec( "40dca5" ),

            // Footer
            "footer" => [
                "text" => "www.paypal.me/xCaliBRScans",
                "icon_url" => "https://i.imgur.com/wKnrGtv.png"
            ],
			
			// Image
			"image" => [
        		"url" => "https://i.imgur.com/hcSgT8M.gif"
      		],

            // Thumbnail
            "thumbnail" => [
                "url" => "$thumbnail"
            ],

            // Author
            "author" => [
                "name" => "xCaliBR Scans",
                "url" => "https://xcalibrscans.com"
            ],
        ]
    ]

], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

  $curl = curl_init($webhookURL);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");  
  curl_setopt($curl, CURLOPT_POSTFIELDS, $embed);
  curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($curl, CURLOPT_HEADER, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
   
  $response = curl_exec($curl);
  $errors = curl_error($curl);        
   
  log_message($errors);
}

function log_message($log) {
    if (true === WP_DEBUG) {
          if (is_array($log) || is_object($log)) {
              error_log(print_r($log, true));
          } else {
              error_log($log);
          }
      }
}

add_action('transition_post_status', 'notification_to_discord', 10, 3);

function notification_to_discord_section_callback() {
echo "<p>A valid Discord Webhook URL to the announcements channel is required.";
}

function notification_to_discord_input_callback() {

echo '<input name="discord_webhook_url" id="discord_webhook_url" type="text" value="' . get_option('discord_webhook_url') . '">';
}

function notification_to_discord_settings_init() {
add_settings_section(
 'discord_webhook_url',
 'Notification to Discord',
 'notification_to_discord_section_callback',
 'general'
);

add_settings_field(
 'discord_webhook_url',
 'Discord Webhook URL',
 'notification_to_discord_input_callback',
 'general',
 'discord_webhook_url'
);

register_setting( 'general', 'discord_webhook_url' );
}

add_action( 'admin_init', 'notification_to_discord_settings_init' );