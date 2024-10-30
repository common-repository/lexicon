<?php
/*
Plugin Name: lexicon
Plugin URI: http://schipplock.de
Description: Create an easy to use lexicon.
Author: Andreas Schipplock
Version: 1.0
Author URI: http://schipplock.de
*/
/*  Copyright 2008  Andreas Schipplock  (email : andreas@schipplock.de)
**
**  This program is free software; you can redistribute it and/or modify
**  it under the terms of the GNU General Public License Version 2, June 1991 as published by
**  the Free Software Foundation
**
**  This program is distributed in the hope that it will be useful,
**  but WITHOUT ANY WARRANTY; without even the implied warranty of
**  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
**  GNU General Public License for more details.
**
**  You should have received a copy of the GNU General Public License
**  along with this program; if not, write to the Free Software
**  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
require_once(dirname(__FILE__).'/../../../wp-config.php');
require_once(dirname(__FILE__).'/../../../wp-admin/upgrade-functions.php');
require_once(dirname(__FILE__).'/libs/htmlparser.inc.php');

class Lexicon {
	function Lexicon() {
		if (isset($this)) {
			add_action('init', array(&$this, 'as_lexicon_init'));
			add_action('admin_menu', array(&$this, 'as_lexicon_admin'));
			add_action('activate_lexicon/lexicon.php', array(&$this, 'as_lexicon_setup'));
			add_filter('the_content', array(&$this, 'as_lexicon_look_for_keywords'));
			add_filter('rewrite_rules_array', array(&$this, 'as_lexicon_influence_rewrite_rules'));
			add_filter('query_vars', array(&$this, 'as_lexicon_query_vars'));
			add_action('parse_query', array(&$this, 'as_lexicon_parse_query'));
		}
	}
	
	function as_lexicon_init() {
		global $wp_rewrite;
		
		if ($wp_rewrite->using_permalinks()==true) {
			update_option("as_lexicon_seolinks", "true", "search engine optimized links", "no");
		} else {
			update_option("as_lexicon_seolinks", "false", "search engine optimized links", "no");
		}
		
		if (get_option("as_lexicon_did_rewrite_regen")=="false") {
			$wp_rewrite->flush_rules();
			update_option("as_lexicon_did_rewrite_regen", "true", "rewrite ruleset regeneration", "no");
		}
	}
	
	function as_lexicon_setup() {
		global $wpdb;
		$as_lexicon_db_version = "1.0";

		$table_name = $wpdb->prefix . "lexicon";

		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			$sql = "CREATE TABLE ".$table_name." (
						id bigint not null auto_increment,
						keyword varchar(255) not null,
						content text not null,
						unique key id (id),
						unique key keyword (keyword)
					);";
			dbDelta($sql);
			
			//adding initial data
			$insert = "INSERT INTO " . $table_name .
			            " (keyword,content) " .
			            "VALUES ('question','the question is what is the question')";

			$results = $wpdb->query( $insert );
			if (get_option("as_lexicon_db_version")=="") {
				add_option("as_lexicon_db_version", $as_lexicon_db_version);
			} else {
				update_option("as_lexicon_db_version", $as_lexicon_db_version);
			}
			
			if (get_option("as_lexicon_showFooter")=="") {
				add_option("as_lexicon_showFooter", "true", "footer", "no");
			} else {
				update_option("as_lexicon_showFooter","true", "footer", "no");
			}
			
			if (get_option("as_lexicon_seolinks")=="") {
				add_option("as_lexicon_seolinks", "false", "search engine optimized links", "no");
			} else {
				update_option("as_lexicon_seolinks", "false", "search engine optimized links", "no");
			}
			
			if (get_option("as_lexicon_seotitles")=="") {
				add_option("as_lexicon_seotitles", "true", "search engine optimized titles", "no");
			} else {
				update_option("as_lexicon_seotitles", "true", "search engine optimized titles", "no");
			}
			
			if (get_option("as_lexicon_did_rewrite_regen")=="") {
				add_option("as_lexicon_did_rewrite_regen","false", "regenerated the rewrite rules", "no");
			} else {
				update_option("as_lexicon_did_rewrite_regen","false", "regenerated the rewrite rules", "no");
			}
		} else {
			if (get_option("as_lexicon_db_version")=="") {
				add_option("as_lexicon_db_version", $as_lexicon_db_version);
			} else {
				update_option("as_lexicon_db_version", $as_lexicon_db_version);
			}
			
			if (get_option("as_lexicon_showFooter")=="") {
				add_option("as_lexicon_showFooter", "true", "footer", "no");
			} else {
				update_option("as_lexicon_showFooter","true", "footer", "no");
			}
			
			if (get_option("as_lexicon_seolinks")=="") {
				add_option("as_lexicon_seolinks", "false", "search engine optimized links", "no");
			} else {
				update_option("as_lexicon_seolinks", "false", "search engine optimized links", "no");
			}
			
			if (get_option("as_lexicon_seotitles")=="") {
				add_option("as_lexicon_seotitles", "true", "search engine optimized titles", "no");
			} else {
				update_option("as_lexicon_seotitles", "true", "search engine optimized titles", "no");
			}
			
			if (get_option("as_lexicon_did_rewrite_regen")=="") {
				add_option("as_lexicon_did_rewrite_regen","false", "regenerated the rewrite rules", "no");
			} else {
				update_option("as_lexicon_did_rewrite_regen","false", "regenerated the rewrite rules", "no");
			}
		}
	}
	
	function as_lexicon_admin() {
		add_options_page('Lexicon', 'Lexicon', 8, 'lexicon-options', array(&$this,'as_lexicon_options'));
		add_management_page('Lexicon', 'Lexicon', 8, 'lexicon', array(&$this, 'as_lexicon_manage'));
		add_submenu_page('post-new.php', 'Lexicon', 'Lexicon', 8, "lexicon", array(&$this, 'as_lexicon_write'));
	}
	
	function as_lexicon_options() {
		if ($_POST["doSave"]=="true") {
			if ($_POST["showFooter"] == "yes") {
				update_option("as_lexicon_showFooter", "true");
			} else {
				update_option("as_lexicon_showFooter", "false");
			}
			if ($_POST["seotitles"] == "yes") {
				update_option("as_lexicon_seotitles", "true");
			} else {
				update_option("as_lexicon_seotitles", "false");
			}
		}
		
		$checkStatus1 = "";
		(get_option("as_lexicon_showFooter")=="true")? $checkStatus1='checked="checked"' : $checkStatus1='';
		
		$checkStatus2 = "";
		(get_option("as_lexicon_seotitles")=="true")? $checkStatus2='checked="checked"':$checkStatus2='';
		
		require_once(dirname(__FILE__)."/templates/options.html");
	}
	
	function as_lexicon_manage() {
		global $wpdb;
		$table_name = $wpdb->prefix . "lexicon";
		
		# if a deletion was requested
		if ($_GET["action"] == "delete") {
			if (is_numeric($_GET["id"])==true) {
				$sql = "delete from ".$table_name." where id = ".$_GET["id"].";";
				$res = mysql_query($sql) or die("dberr");
				$message = "Your lexicon entry has been successfully deleted.";
				require(dirname(__FILE__)."/templates/message.html");
			}
		}
		
		# edit a keyword entry
		if ($_GET["action"] == "edit") {
			if ($_GET["updateData"] == "true") {
				# update the data for the keyword in database
				if (is_numeric($_GET["id"])==true) {
					$status = "ok";
					if ($_POST["post_title"]=="") {
						$message = "ERROR: the keyword cannot be empty.";
						require(dirname(__FILE__)."/templates/message.html");
						$status = "failed";
					}
					if ($_POST["content"]=="") {
						$message = "ERROR: the content cannot be empty.";
						require(dirname(__FILE__)."/templates/message.html");
						$status = "failed";
					}
					if ($status != "failed") {
						$id = $_GET["id"];
						$keyword = $_POST["post_title"];
						$content = addslashes($_POST["content"]);
						
						$sql = "update $table_name set keyword='$keyword', content='$content' where id='$id' limit 1;";
						mysql_query($sql) or die("dberr-on-update");
						$message = "Success.";
						require_once(dirname(__FILE__)."/templates/message.html");

						if (is_numeric($_GET["id"])==true) {
							$title = "Edit Lexicon Entry";
							$action = "edit.php?page=lexicon&action=edit&id=$id&updateData=true";
							require_once(dirname(__FILE__)."/templates/write.html");
						}
					}
				}
			} else {
				# display the editor with content for the keyword
				if (is_numeric($_GET["id"])==true) {
					$id = $_GET["id"];
					$sql = "select id,keyword,content from $table_name where id='$id' limit 1;";
					$res = mysql_query($sql) or die("dberr");
					list($id,$keyword,$content)=mysql_fetch_array($res);
					$content = stripslashes($content);
					$title = "Edit Lexicon Entry";
					$action = "edit.php?page=lexicon&action=edit&id=$id&updateData=true";
					require_once(dirname(__FILE__)."/templates/write.html");
				}
			}
		}
		
		# list all lexicon keywords/entries
		$sql = "select id,keyword from ".$table_name." order by id desc;";
		$res = mysql_query($sql) or die("dberr");
		
		$tableData = array();
		while(list($id,$keyword)=mysql_fetch_array($res)) {
			array_push($tableData, array("id"=>$id,"keyword"=>$keyword));
		}
		
		require_once(dirname(__FILE__)."/templates/manage.html");
	}
	
	function as_lexicon_look_for_keywords($content) {
		global $wpdb;
		global $wp_query;
		
		$table_name = $wpdb->prefix . "lexicon";
		# seo links?
		$seo = "false";
		(get_option("as_lexicon_seolinks")=="true")?$seo=true:$seo=false;
		# get all keywords from database
		$sql = "select id,keyword from $table_name;";
		$res = mysql_query($sql) or die("dberr on the_content");
		$newcontent = "";
		$keywordcount = 0;
		$keywords = array();
		#build the keywords array
		while(list($id,$keyword)=mysql_fetch_array($res)) {
			# when keyword is found
			if (strpos($content,$keyword)!==false) {
				$keywordcount++;
				array_push($keywords, array("name"=>$keyword, "id"=>$id));
			}
		}
		# parse the html (if any) for text elements
		# only in text elements we insert the href's to our lexicon
		# prepare the html parser object
		$parser = new HtmlParser($content);
		while ($parser->parse()) {   
			if ($parser->iNodeType == NODE_TYPE_TEXT) {
				for ($run=0;$run<count($keywords);$run++) {
					# in case a keyword contains whitespaces
					$dKeyword = str_replace(" ", "-", $keywords[$run]["name"]);
					if ($seo==false) {
						$parser->iNodeValue = str_replace($keywords[$run]["name"],"<a href=\"/?asenciclopedia=true&id=".$keywords[$run]["id"]."\">".$keywords[$run]["name"]."</a>", $parser->iNodeValue);
					} else {
						$parser->iNodeValue = str_replace($keywords[$run]["name"],"<a href=\"/lexicon/$dKeyword-".$keywords[$run]["id"]."/\">".$keywords[$run]["name"]."</a>", $parser->iNodeValue);
					}
				}
				$newcontent .= $parser->iNodeValue;
			} else {
				if ($parser->iNodeType == NODE_TYPE_ENDELEMENT) {
					$newcontent .= "</".$parser->iNodeName;
				} else {
					if (count($parser->iNodeAttributes)==0) {
						$newcontent .= "<".$parser->iNodeName;
					} else {
						$newcontent .= "<".$parser->iNodeName." ";
					}
				}
				$counter = 0;
				foreach($parser->iNodeAttributes as $index=>$value) {
					$counter++;
					if ($counter<=(count($parser->iNodeAttributes)-1)) {
						$newcontent .= $index."=\"".$value."\" ";
					} else {
						$newcontent .= $index."=\"".$value."\"";
					}
				}
				if ($parser->iNodeName=="img") {
					$newcontent .= " />";
				} else {
					$newcontent .= ">";
				}
			}
		}
			
		
		
		if ($keywordcount==0) {
			$newcontent = $content;
		}
		if ($wp_query->query_vars['asenciclopedia']=="true") {
			$newcontent = $content;
		}
		
		return $newcontent;
	}
	
	function as_lexicon_write() {
		global $wpdb;
		$table_name = $wpdb->prefix . "lexicon";
		
		if ($_POST["action"]=="save") {
			$keyword = $_POST["post_title"];
			$content = $_POST["content"];
			
			# check if keyword already exists
			$res = mysql_query("select keyword from $table_name where keyword='$keyword' limit 1;");
			list($dbkeyword)=mysql_fetch_array($res);
			if ($dbkeyword!=$keyword) {
				$content = addslashes($_POST["content"]);
				$insert = "INSERT INTO " . $table_name .
							" (keyword,content) " .
							"VALUES ('$keyword','$content')";
				$results = $wpdb->query( $insert );
				$message = "Your lexicon entry has been saved.";
				$keyword = "";
				$content = "";
				require_once(dirname(__FILE__)."/templates/message.html");
			} else {
				$content = stripslashes($_POST["content"]);
				$message = "";
				if ($keyword=="") {
					$message = "ERROR: the keyword cannot be empty.";
					require(dirname(__FILE__)."/templates/message.html");
				}
				if ($content=="") {
					$message = "ERROR: the content cannot be empty.";
					require(dirname(__FILE__)."/templates/message.html");
				}
				if ($keyword != "") {
					$message = "ERROR: the keyword already exists in database.";
					require(dirname(__FILE__)."/templates/message.html");
				}
			}
		}
		$title = "Write Lexicon Entry";
		$action = "post-new.php?page=lexicon";
		require_once(dirname(__FILE__)."/templates/write.html");
	}
	
	function as_lexicon_influence_posts($posts) {
		global $wpdb;
		global $wp_query;
		$table_name = $wpdb->prefix . "lexicon";
		
		# if you set seo titles to yes the blogname and title will be changed
		# but only if you are viewing a lexicon entry of course
		if (get_option("as_lexicon_seotitles")=="true") {
			add_filter('wp_title', array(&$this, 'as_lexicon_modify_title'));
			add_filter('bloginfo', array(&$this, 'as_lexicon_bloginfo'), 1, 2);
		}
		
		if (is_numeric($wp_query->query_vars['id'])==true) {
			$id = $wp_query->query_vars['id'];
			$res = mysql_query("select id,keyword,content from $table_name where id='$id' limit 1;");
			list($dbid,$dbkeyword,$dbcontent)=mysql_fetch_array($res) or die("dberr on select");
			$keyword = $dbkeyword;
			$content = stripslashes($dbcontent);
			$showFooter = false;
			(get_option("as_lexicon_showFooter")=="true")?$showFooter=true:$showFooter=false;
			
			//$content = str_replace("\\\"","\"",$content);
			
			# back button?
			if (strpos($_SERVER["HTTP_REFERER"], get_option("siteurl"))!==false) {
				$backlink = "<br /><br /><a href=\"".$_SERVER["HTTP_REFERER"]."\">&lt;&lt; Back</a>";
				$content = $content.$backlink;
			}
			
			# footer?
			if (get_option("as_lexicon_showFooter")=="true") {
				$footer = "<br /><br /><i>Powered by <a href=\"http://schipplock.de\">Schipplock's</a> lexicon plugin</i><br /><br />";
				$content = $content.$footer;
			}

			$posts[0]->{"post_content"} = $content;
			$posts[0]->{"post_title"} = $keyword;
			$posts[0]->{"ID"} = "";
			$posts[0]->{"post_author"} = "";
			$posts[0]->{"post_date"} = "";
			$posts[0]->{"post_date_gmt"} = "";
			$posts[0]->{"post_category"} = "";
			$posts[0]->{"post_excerpt"} = "";
			$posts[0]->{"post_status"} = "publish";
			$posts[0]->{"comment_status"} = "close";
			$posts[0]->{"ping_status"} = "close";
			$posts[0]->{"post_password"} = "";
			$posts[0]->{"post_name"} = "";
			$posts[0]->{"to_ping"} = "";
			$posts[0]->{"pinged"} = "";
			$posts[0]->{"post_modified"} = "";
			$posts[0]->{"post_modified_gmt"} = "";
			$posts[0]->{"post_content_filtered"} = "";
			$posts[0]->{"post_parent"} = 0;
			$posts[0]->{"guid"} = "#";
			$posts[0]->{"menu_order"} = 0;
			$posts[0]->{"post_type"} = "post";
			$posts[0]->{"post_mime_type"} = "";
			$posts[0]->{"comment_count"} = 0;
		}
		$entry = array($posts[0]);
		return $entry;
	}
	
	function as_lexicon_influence_rewrite_rules($rules) {
		$newrules = array();
		$newrules['lexicon/(.+)-(.+)$']='index.php?id=$matches[2]&asenciclopedia=true';
		# we need to flush the rewrite rules on init, otherwise they have no effect
		update_option("as_lexicon_did_rewrite_regen", "false", "rewrite ruleset regeneration", "no");
		return $newrules+$rules;
	}
	
	function as_lexicon_query_vars($vars) {
		array_push($vars, 'asenciclopedia', 'id');
		return $vars;
	}
	
	function as_lexicon_parse_query($query) {
		if ($query->query_vars['asenciclopedia']=="true") {
			add_filter('the_posts', array(&$this, 'as_lexicon_influence_posts'));
		}
	}
	
	function as_lexicon_modify_title($title) {
		# I can print the title here but as almost all themes are 
		# printing bloginfo('name') first before they print wp_title
		# I decided not to print the title
		# Instead I catch bloginfo('name') and override the blog's name 
		# which is shown first then
		print "";
	}
	
	function as_lexicon_bloginfo($result='', $show='') {
		global $wpdb;
		global $wp_query;
		$table_name = $wpdb->prefix . "lexicon";
		
		switch ($show) {
        case 'name':{
        		if (is_numeric($wp_query->query_vars['id'])==true) {
					$id = $wp_query->query_vars['id'];
					$res = mysql_query("select id,keyword from $table_name where id='$id' limit 1;");
					list($dbid,$dbkeyword)=mysql_fetch_array($res) or die("dberr on select");
					$keyword = $dbkeyword;
                	$result = $keyword." - ".get_option("blogname");
                }
                break;
        }
        default: 
        }
        return $result;
	}
}

$myLexicon = new Lexicon();

?>
