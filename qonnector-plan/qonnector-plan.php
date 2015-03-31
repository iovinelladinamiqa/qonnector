<?php
/**
 * Plugin Name: Qonnector Plan
 * Plugin URI: #
 * Description: Connetti il tuo Wordpress al Qonnecta Plan.
 * Version: 1.0.1
 * Author: Vittorio Iovinella
 * Author URI: iovinella@dinamiqa.com
 * License: GPL2
 */

class Qonnector {

	public function __construct(){
		if (!session_id()) {
		    @session_start();
		}
		add_action( 'load-post-new.php', array(__CLASS__, "check_new_post_request") );
		add_action( 'load-post.php', array(__CLASS__, "check_edit_post_request") );
		add_action( 'save_post', array(__CLASS__, "add_plan_data_to_post"),10,2);
	}

	/**
    * Controlla che la richiesta di creazione articolo provenga dal plan (Scrivi Articolo).
    */
	static function check_new_post_request(){
		if(isset($_GET['qpa']) && $_GET['qpa'] == 'write' && isset($_GET['kid'])){
			// Salva keyword_plan_id nella sessione per non perderla al salvataggio
			$_SESSION['qonnector_plan_action'] = $_GET['qpa'];
			$_SESSION['qonnector_plan_keyword_id'] = $_GET['kid'];
		}
	}

	/**
    * Controlla che la richiesta di modifica articolo provenga dal plan (Revisiona Articolo).
    */
	static function check_edit_post_request(){
		if(isset($_GET['qpa']) && $_GET['qpa'] == 'review' && isset($_GET['kid'])){
			// Salva keyword_plan_id nella sessione per non perderla al salvataggio
			$_SESSION['qonnector_plan_action'] = $_GET['qpa'];
			$_SESSION['qonnector_plan_keyword_id'] = $_GET['kid'];
		}		
	}

	/**
    * Associa la keyword del plan all'articolo.
    */
	static function add_plan_data_to_post($postId,$post){
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && ($post->post_status == 'publish' || $post->post_status == 'draft' || $post->post_status == 'future') ){
			//print_r($post);
			//print_r($_SESSION);
			switch ($_SESSION['qonnector_plan_action']) {
				case 'write':
					add_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'],true);
					$action = 'a=setreview';
					break;
				
				case 'review':
					// Passa la data di pubblicazione per registrare le date dei post programmati
					//echo $post->post_date;
					$publishDate = $post->post_date;
					$action = 'a=setpublish&d='.$publishDate;
					break;
			}

			// Aggiorna Plan
			$base_url = 'http://qonnecta.com/plan/rest/index.php';			
			$kid = 'kid='.$_SESSION['qonnector_plan_keyword_id'];
			$pid = 'pid='.$postId;
			$purl = 'purl='. urlencode(admin_url().'post.php?post='.$postId.'&action=edit&qpa=review&'.$kid);
			$request_url = $base_url.'?'.$action.'&'.$kid.'&'.$pid.'&'.$purl;
			$result = json_decode(file_get_contents($request_url));
			unset($_SESSION['qonnector_plan_keyword_id']);
			unset($_SESSION['qonnector_plan_action']);	
		}		
	}
}

new Qonnector();
?>
