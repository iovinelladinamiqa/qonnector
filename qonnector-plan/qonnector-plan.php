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
		add_action( 'load-post-new.php', array(__CLASS__, "check_new_post_request"),11,1 );
		add_action( 'load-post.php', array(__CLASS__, "check_edit_post_request"));
		add_action( 'save_post', array(__CLASS__, "add_plan_data_to_post"),10,2);
		add_action( 'load-edit.php', array(__CLASS__, "correct_user_role"));
		//add_filter( 'redirect_post_location', array(__CLASS__, "add_plan_data_to_post"));
	}

	/**
    * Controlla che la richiesta di creazione articolo provenga dal plan (Scrivi Articolo).
    */
	static function check_new_post_request(){
		
		if(isset($_GET['qpa']) && $_GET['qpa'] == 'write' && isset($_GET['kid'])){
			// Utente loggato
			$current_user = wp_get_current_user();

			// Salva il ruolo naturale dell'utente loggato
			$_SESSION['qonnector_current_user_role'] = $current_user->roles[0];
			$_SESSION['qonnector_plan_user_id'] = $current_user->ID;
			$_SESSION['qonnector_post_status'] = 'pending';
			// Salva keyword_plan_id nella sessione per non perderla al salvataggio
			$_SESSION['qonnector_plan_action'] = $_GET['qpa'];
			$_SESSION['qonnector_plan_keyword_id'] = $_GET['kid'];
			// Setta il ruolo dell'utente a COLLABORATORE 
			//$current_user->set_role('contributor'); 
		}
	}

	/**
    * Controlla che la richiesta di modifica articolo provenga dal plan (Revisiona Articolo).
    */
	static function check_edit_post_request(){
		$postId = $_GET['post'];
		$current_user = wp_get_current_user();
		if(isset($_GET['qpa']) && $_GET['qpa'] == 'review' && isset($_GET['kid'])){
			$_SESSION['qonnector_plan_action'] = $_GET['qpa'];
			$_SESSION['qonnector_plan_keyword_id'] = $_GET['kid'];
			$_SESSION['qonnector_current_user_role'] = $current_user->roles[0];
			$_SESSION['qonnector_plan_user_id'] = $current_user->ID;
			$_SESSION['qonnector_post_status'] = 'pending';
		} else {
			$plan_user = get_post_meta($postId,'qonnector_plan_user_id',true);
			$post_status = get_post_meta($postId,'qonnector_post_status',true);
			$action = get_post_meta($postId,'qonnector_plan_action',true);
			$keywor_id = get_post_meta($postId,'qonnector_plan_keyword_id',true);			
			if($plan_user == $current_user->ID && ($post_status == 'pending' || $post_status == 'completed') ){
				$_SESSION['qonnector_plan_action'] = $action;
				$_SESSION['qonnector_plan_keyword_id'] = $keywor_id;
				$_SESSION['qonnector_current_user_role'] = $current_user->roles[0];
				$_SESSION['qonnector_plan_user_id'] = $current_user->ID;
				$_SESSION['qonnector_post_status'] = $post_status;
				/*
				if($action=='write'){
					$current_user->set_role('contributor'); 
				}
				*/
			}
		}	
	}

	/**
    * Associa la keyword del plan all'articolo.
    */
	static function add_plan_data_to_post($postId,$post){
		// Se lo SCRITTORE invia un POST per la REVISIONE (pending)
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && $_SESSION['qonnector_plan_action'] == 'write' && $post->post_status == 'pending' && $post->post_type == 'post' ){
			// Associa la keyword al post
			if(!update_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'])){
				add_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'],true);
			}
			// Associa l'id dell'autore al post
			if(!update_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'])){
				add_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'],true);
			}
			// Registra lo stato del post
			if(!update_post_meta($postId, 'qonnector_post_status', 'completed')){
				add_post_meta($postId, 'qonnector_post_status', 'completed',true);
			}
			// Registra l'azione sul post
			if(!update_post_meta($postId, 'qonnector_plan_action', 'write')){
				add_post_meta($postId, 'qonnector_plan_action', 'write',true);
			}

			// Riassegna il ruolo originale all'utente
			/*
			$current_user = wp_get_current_user();
			$current_user->set_role($_SESSION['qonnector_current_user_role']);
			*/

			// Setta il parametro per la chiamata al plan		
			$action = 'a=setreview';

			
		}

		// Se lo SCRITTORE salva una BOZZA
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && $_SESSION['qonnector_plan_action'] == 'write' && $post->post_status == 'draft' && $post->post_type == 'post' ){
			// Associa la keyword al post
			if(!update_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'])){
				add_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'],true);
			}
			// Associa l'id dell'autore al post
			if(!update_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'])){
				add_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'],true);
			}
			// Registra lo stato del post
			if(!update_post_meta($postId, 'qonnector_post_status', 'pending')){
				add_post_meta($postId, 'qonnector_post_status', 'pending',true);
			}
			// Registra l'azione sul post
			if(!update_post_meta($postId, 'qonnector_plan_action', 'write')){
				add_post_meta($postId, 'qonnector_plan_action', 'write',true);
			}	

			// Riassegna il ruolo originale all'utente
			/*
			$current_user = wp_get_current_user();
			$current_user->set_role($_SESSION['qonnector_current_user_role']);	
			*/
		}		


		// Se il REVISORE SALVA (publica o programma) un POST
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && $_SESSION['qonnector_plan_action'] == 'review' && ($post->post_status == 'publish' || $post->post_status == 'future') && $post->post_type == 'post' ){
			// Associa la keyword al post
			if(!update_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'])){
				add_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'],true);
			}
			// Associa l'id dell'autore al post
			if(!update_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'])){
				add_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'],true);
			}
			// Registra lo stato del post
			if(!update_post_meta($postId, 'qonnector_post_status', 'completed')){
				add_post_meta($postId, 'qonnector_post_status', 'completed',true);
			}
			// Registra l'azione sul post
			if(!update_post_meta($postId, 'qonnector_plan_action', 'review')){
				add_post_meta($postId, 'qonnector_plan_action', 'review',true);
			}
			// Setta i parametri per la chiamata al plan
			$publishDate = urlencode($post->post_date);
			$action = 'a=setpublish&d='.$publishDate;
		}

		// Se il revisore SALVA una BOZZA
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && $_SESSION['qonnector_plan_action'] == 'review' && $post->post_status == 'pending' && $post->post_type == 'post' ){
			// Associa la keyword al post
			if(!update_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'])){
				add_post_meta($postId, 'qonnector_plan_keyword_id', $_SESSION['qonnector_plan_keyword_id'],true);
			}
			// Associa l'id dell'autore al post
			if(!update_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'])){
				add_post_meta($postId, 'qonnector_plan_user_id', $_SESSION['qonnector_plan_user_id'],true);
			}
			// Registra lo stato del post
			if(!update_post_meta($postId, 'qonnector_post_status', 'pending')){
				add_post_meta($postId, 'qonnector_post_status', 'pending',true);
			}
			// Registra l'azione sul post
			if(!update_post_meta($postId, 'qonnector_plan_action', 'review')){
				add_post_meta($postId, 'qonnector_plan_action', 'review',true);
			}
		}
		
		// Se il post viene SALVATO (publica o programma x il REVISTORE, Invia per revisione [COLLABORATORE]) aggiorna il plan
		if(isset($_SESSION['qonnector_plan_keyword_id']) && isset($_SESSION['qonnector_plan_action']) && ($post->post_status == 'publish' || $post->post_status == 'pending' || $post->post_status == 'future') && $post->post_type == 'post' ){
			
			// Aggiorna Plan
			$base_url = 'http://qonnecta.com/plan/rest/index.php';			
			$kid = 'kid='.$_SESSION['qonnector_plan_keyword_id'];
			$pid = 'pid='.$postId;
			$purl = 'purl='. urlencode(admin_url().'post.php?post='.$postId.'&action=edit&qpa=review&'.$kid);
			$request_url = $base_url.'?'.$action.'&'.$kid.'&'.$pid.'&'.$purl;
			$result = json_decode(file_get_contents($request_url));
			//echo $result;
						
		}	

		//var_dump( get_query_var('action') );
		if(isset($_SESSION['qonnector_plan_action']) == 'write' && $post->post_type == 'post' && ($post->post_status == 'publish' || $post->post_status == 'pending' || $post->post_status == 'future' || $post->post_status == 'draft')){
			/*
			$url = admin_url().'edit.php';
			wp_redirect(apply_filters( 'redirect_post_location', $url, $_GET['post']));
			exit;
			*/
		}
				
	}

	static function correct_user_role(){
		if(isset($_SESSION)){
			if(isset($_SESSION['qonnector_current_user_role'])){
				//echo $_SESSION['qonnector_current_user_role'];
				/*
				$current_user = wp_get_current_user();
				$current_user->set_role($_SESSION['qonnector_current_user_role']);
				*/

				unset($_SESSION['qonnector_plan_keyword_id']);
				unset($_SESSION['qonnector_plan_action']);	
				unset($_SESSION['qonnector_current_user_role']);
				unset($_SESSION['qonnector_plan_user_id']);
				unset($_SESSION['qonnector_post_status']);

				/*
				$url = admin_url().'edit.php';
				wp_redirect(apply_filters( 'redirect_post_location', $url, $_GET['post']));
				exit;
				*/
			}

		}
	}
	
}

new Qonnector();
?>
