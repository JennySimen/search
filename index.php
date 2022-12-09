<?php


/**
 * If $engine is set in the request, then the user must have javascript turned
 * off, else they would have gone directly to the search site, and not back to
 * index.php.  This block handles those who don't uses javascript for whatever
 * reason and could therefor be easily used as an API.
 */
$query = isset($_REQUEST['query']) ? $_REQUEST['query'] : ''; // moving this here, we want to let people search and come here

if ( isset($_REQUEST['engine']) && $_REQUEST['query'] != "" ) {

	$engine = $_REQUEST['engine'];
	$comm = isset($_REQUEST['comm']) ? TRUE : FALSE;
	$deriv = isset($_REQUEST['deriv']) ? TRUE : FALSE;

	// We never want the search to execute with the default text
	if ( $query == "Enter search query" ) {
		$query = "flowers";
	}

	$rights = modRights($engine, $comm, $deriv);

	$url = "";

    // NOTE: if you make changes here, you should make a similar change in search.js
	switch ( $engine ) {

		case "openverse":
			$url = 'https://wordpress.org/openverse/search/?q=' . $query . '&license_type='. $rights;
			break;

		case "openclipart":
			$url = 'http://openclipart.org/search/?query=' . $query;
			break;

		case "jamendo":
			if ( $rights ) {
				$url = 'https://licensing.jamendo.com/en/royalty-free-music/search?qs=' . 'query=' . $query;
			} else {
				$url = 'http://www.jamendo.com/search?q=tag:' . $query;
			}
			break;

		case "flickr":
			$url = 'http://flickr.com/search/?' . $rights . '&q=' . $query;
			break;

		case "wikimediacommons":
			$url = 'http://commons.wikimedia.org/w/index.php?title=Special%3ASearch&redirs=0&search=' . $query . '&fulltext=Search&ns0=1&ns6=1&ns14=1&title=Special%3ASearch&advanced=1&fulltext=Advanced+search';
			break;

		case "fotopedia":
			$url = 'http://www.fotopedia.com/search?q=' . $query . '&human_license=' . $rights;
			break;

		case "europeana":
			$url = 'http://www.europeana.eu/portal/search.html?query=' . $query . $rights;
			break;

		case "youtube":
			$url = 'http://www.youtube.com/results?search_query=' . $query . ',creativecommons';
			break;

		case "ccmixter":
			$url = 'http://ccmixter.org/api/query?datasource=uploads&search_type=all&sort=rank&search=' . $query . $rights;
			break;

		case "soundcloud":
			$url = 'https://soundcloud.com/search/sounds?q=' . $query . $rights;
			break;

		case "thingiverse":
			// Defer to OpenVerse (with search refined only to Thingiverse items) until Thingiverse licence search filter issue is fixed
			// TODO Use Thingiverse search filters when issue is fixed and
			$url = 'https://wordpress.org/openverse/search/image?q=' . $query . '&source=thingiverse' . $rights;
			// $url = 'https://www.thingiverse.com/search?q=' . $query . $rights;
			break;

		case "sketchfab":
			$url = 'https://sketchfab.com/search?q=' . $query . $rights;
			break;

		case "nappy":
				$url = 'https://www.nappy.co/search/' . $query ;
				break;	

		case "vimeo":
			    $url = 'https://vimeo.com/search?' . $rights + '&q=' + $query;
				break;

		case "googleimg":
			$url = 'https://www.google.com/search?site=imghp&tbm=isch&q=';

		case "google":
		default:
			$url = $url ? $url : 'http://google.com/search?q=';
			$url .= $query . '&as_rights=' . $rights;
			break;

	}

        $url = urlencode($url);

	header('Location: https://oldsearch.creativecommons.org/bouncer.php?q=' . $query . '&url=' . $url);
	exit;

}

/**
 * Sets up the right query string for the various content providers.
 */
function modRights($engine, $comm, $deriv) {

	$rights = "";

	switch ( $engine ) {

		case "google":
		case "googleimg":
			/*
			fmc	Labeled for reuse with modification
			fc	Labeled for reuse
			fm	Labeled for noncommercial reuse with modification
			f	Labeled for noncommercial reuse
			*/
			$rights = "&tbs=sur:f";
			$rights .= $comm ? "m" : "";
			$rights .= $deriv ? "c" : "";

			break;

		case "thingiverse":
			/*
				If $comm or $deriv is provided, then we first apply the filter
				to capture only results of type "things" before adding the specifics we require because
				the "customizable" and "licence" filters on thingiverse only work alongside the "things" filter
			*/

			if ($comm || $deriv) {
				// Defer to OpenVerse (with search refined only to Thingiverse items) until Thingiverse licence search filter issue is fixed
				// TODO Use Thingiverse search filters when issue is fixed and

				// Replicate Openverse rights when commercial and/or modify filters selected
				$rights = '&license_type=';
				if( $comm && $deriv){
					$rights .= "commercial,modification";
				} elseif($comm){
					$rights .= "commercial";
				}elseif($deriv){
					$rights .= "modification";
				}

			} 
			// else {
			// 	if ($comm || $deriv) {
			// 		$rights = "&type=things&sort=relevant";
			// 		$rights .= $deriv ? "&customizable=1" : "";
	
			// 		// Used the licence=cc (which on Thingiverse, stands for the Creative Commons Attribution license)
			// 		// as the equivalent for the "modify, reuse ..." filter on CC search
			// 		$rights .= $comm ? "&license=cc": "";
			// 	}
			// }

			break;

		case "sketchfab":
			/*	Licenses
				CC BY -> Author must be credited. Derivatives allowed. Commercial use allowed. code=322a749bcfa841b29dff1e8a1bb74b0b
				CC BY-SA -> Author must be credited. Derivatives allowed, same licence as original. Commercial use allowed. code=b9ddc40b93e34cdca1fc152f39b9f375
				CC BY-ND -> Author must be credited. No Derivatives. Commercial use allowed. code=72360ff1740d419791934298b8b6d270
				CC BY-NC -> Author must be credited. Derivatives allowed. No Commercial Use. code=bbfe3f7dbcdd4122b966b85b9786a989
				CC BY-NC-SA -> Author must be credited. Derivatives allowed, same license as original. No Commercial Use. code=2628dbe5140a4e9592126c8df566c0b7
				CC0 -> Credit is not manadatory. Derivatives allowed. Commercial Use allowed. code=7c23a1ba438d4306920229c12afcb5f9
			*/

			$rights = "";

			// If $comm or $deriv is provided, then we first apply the downloadable filter
			if ($comm || $deriv) {
				$rights = "&features=downloadable"
					. "&licenses=322a749bcfa841b29dff1e8a1bb74b0b" // Include CC BY license
					. "&licenses=b9ddc40b93e34cdca1fc152f39b9f375" // Include CC BY-SA license
					. "&licenses=7c23a1ba438d4306920229c12afcb5f9"; // Include CC0

				if ($comm && !$deriv) {
					$rights .= "&licenses=72360ff1740d419791934298b8b6d270"; // Include CC BY-ND
				} else if (!$comm && $deriv) {
					$rights .= "&licenses=bbfe3f7dbcdd4122b966b85b9786a989" // Include CC BY-NC
						. "&licenses=2628dbe5140a4e9592126c8df566c0b7"; // Include CC BY-NC-SA
				}

			}

			break;

		case "flickr":
			$rights = "l=";
			$rights .= $comm ? "comm" : "";
			$rights .= $deriv ? "deriv" : "";
			$rights = ($rights == "l=") ? "l=cc" : $rights;
			break;

		case "openverse":
			if( $comm && $deriv){
				$rights = "commercial,modification";
			} elseif($comm){
				$rights = "commercial";
			}elseif($deriv){
				$rights = "modification";
			}
			break;

		case "jamendo":
			if ( $comm && $deriv ) {
				$rights = '-nc%20AND%20-nd';
			} elseif ( $comm ) {
				$rights = '-nc';
			} elseif ( $deriv ) {
				$rights = '-nd';
			}
			break;

		case "fotopedia":
			if ( $comm && $deriv ) {
				$rights = "reuse_commercial_modify";
			} else if ( $comm && ! $deriv ) {
				$rights = "reuse_commercial";
			} else if ( ! $comm && $deriv ) {
				$rights = "reuse_modify";
			} else {
				$rights = "reuse";
			}
			break;

		case "europeana":
			if ( $comm && $deriv ) {
				$rights = "+AND+RIGHTS%3A*creative*+AND+NOT+RIGHTS%3A*nc*+AND+NOT+RIGHTS%3A*nd*";
			} else if ( $comm && ! $deriv ) {
				$rights = "+AND+RIGHTS%3A*creative*+AND+NOT+RIGHTS%3A*nc*";
			} else if ( ! $comm && $deriv ) {
				$rights = "+AND+RIGHTS%3A*creative*+AND+NOT+RIGHTS%3A*nd*";
			} else {
				$rights = "+AND+RIGHTS%3A*creative*+";
			}
			break;

		case "ccmixter":
			if ( $comm && $deriv ) {
				$rights = "&lic=by,sa,s,splus,pd,zero";
			} else if ( $comm && ! $deriv ) {
				$rights = "&lic=open";
			} else if ( ! $comm && $deriv) {
				$rights = "&lic=by,nc,sa,ncsa,s,splus,pd,zero";
			}
			break;

		case "soundcloud":
			if ( $comm && $deriv ) {
				$rights = "&filter.license=to_modify_commercially";
			} else if ( $comm && ! $deriv ) {
				$rights = "&filter.license=to_use_commercially";
			} else if ( (! $comm && $deriv) || (! $comm && ! $deriv) ) {
				$rights = "&filter.license=to_share";
			}
			break;
			
		case "vimeo":
			if ( $comm && $deriv) {
					$rights = "&license=by";
				} else if ( $comm && ! $deriv ) {
					$rights = "&license=by";
				} else if ( !$comm && $deriv ) {
					$rights = "&license=by-nc";
				}
				break;

	}

	return $rights;

}
    // adaptor code for cc-wp theme
    if ( ! function_exists('bloginfo') ) {
        function bloginfo ($param) {
            if ( $param == 'home' )
                print 'http://creativecommons.org';
            if ( $param == 'stylesheet_directory' ) {
                // print $theme_path . '/cc-wp';
                print '/cc-wp';
            }
        }
    }
    if ( ! function_exists('get_http_security') ) {
        function get_http_security () {
            echo 'https';
        }
    }
    include_once 'cc-wp/meta.php';
    include_once 'cc-wp/header-doctype.php'; ?>

<html lang="en">
	<head>
		<title>CC Search Portal</title>
	    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <?php include 'cc-wp/header-common.php'; ?>

		<link rel="search" type="application/opensearchdescription+xml" title="Creative Commons Search Portal" href="http://oldsearch.creativecommons.org/ccsearch.xml" />
		<link rel="stylesheet" href="style.css" type="text/css" media="screen" title="no title" charset="utf-8" />

		<!--[if lte IE 7]>
		<link rel="stylesheet" href="style-ie.css" type="text/css" media="screen" charset="utf-8" />
		<![endif]-->

		<script src="jquery.js" type="text/javascript" charset="utf-8"></script>
		<script src="search.js" type="text/javascript" charset="utf-8"></script>

		<script src="elog/elog.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body>
	<div id="container">
        <?php include 'cc-wp/page-nav.php'; ?>
        <div id="main" role="main">
        <div class="container">

        <div class="sixteen columns">

		<div class="first row">

        </div>

		<div class="row">
			<div id="search">
				<form id="search_form" method="get" onsubmit="return doSearch()">
            <div class="seven columns alpha">
			<div id="header_logo" title="To search, enter some search terms, then click a provider." onclick="if ( $('#query').val() ) { doSearch(); }">
				<img src="cc-search-portal.png" alt="CC Search Portal" />
				<div id="header_text"><span>Find content you can share, use and remix</span></div>
			</div>
            </div>
            <div class="nine columns omega re">
					<input type="text" id="query" name="query" value="<?php echo $query; ?>" placeholder="Enter your search query"/>
					<div id="secondaryOptions">
						<fieldset id="permissions">
							<small>
                                <div class="statement">
								<strong>I want something that I can...</strong>
                                </div>

                                <div class="permoptions">
								<input type="checkbox" name="comm" value="" id="comm" checked="checked" onclick="setCommDeriv()" />
								<label for="comm"  onclick="setCommDeriv()">use for <em>commercial purposes</em>;</label>
                                </div>
                                <div class="permoptions">
								<input type="checkbox" name="deriv" value="" id="deriv" checked="checked"  onclick="setCommDeriv()" />
								<label for="deriv" onclick="setCommDeriv()"><em>modify</em>, <em>adapt</em>, or <em>build upon</em>.</label><br/>
                                </div>
							</small>
						</fieldset>
					</div>
                </div>

					<fieldset id="engines">
						<p><strong>Search using:</strong></p>

						<div class="first row">
							<div class="four columns alpha">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="ccmixter" id="ccmixter"><strong>ccMixter</strong><br/>Music</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="europeana" id="europeana"><strong>Europeana</strong><br/>Media</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="flickr" id="flickr"><strong>Flickr</strong><br/>Image</button>
							</div>

							<div class="four columns omega">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="google" id="google"><strong>Google</strong><br/>Web</button>
							</div>
						</div>

						<div class="row">
							<div class="four columns alpha">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="googleimg" id="googleimg"><strong>Google Images</strong><br/>Image</button>
							</div>


							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="jamendo" id="jamendo"><strong>Jamendo</strong><br/>Music</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="nappy" id="nappy"><strong>Nappy</strong><br/>image</button>
							</div>

							<div class="four columns omega">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="openclipart" id="openclipart"><strong>Open ClipArt</strong><br/>Image</button>
							</div>
						</div>

						<div class="row">
							<div class="four columns alpha">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="openverse" id="openverse">
								<strong class="openverse">Openverse</strong>
								<br/>Media</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="sketchfab" id="sketchfab"><strong>Sketchfab</strong><br/>3D Model</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="soundcloud" id="soundcloud"><strong>SoundCloud</strong><br/>Music</button>
							</div>

							<div class="four columns omega">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="thingiverse" id="thingiverse"><strong>Thingiverse</strong><br/>3D Model</button>
							</div>
						</div>

						<div class="row">
							<div class="four columns alpha">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="vimeo" id="vimeo"><strong>Vimeo</strong><br/>Video</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="wikimediacommons" id="wikimediacommons"><strong>Wikimedia Commons</strong><br/>Media</button>
							</div>

							<div class="four columns">
								<button class="engine engineDesc" onclick="setEngine(this)" name="engine" value="youtube" id="youtube"><strong>YouTube</strong><br/>Video</button>
							</div>
						</div>
					</fieldset>
				</form>
                </div>
			</div>
		</div>
		<div class="row">
			<div id="help">
                <div class="one columns alpha">
					<p>Please note that CC Search Portal is <em>not a search engine</em>, but rather offers convenient access to search services provided by other independent organizations. CC has no control over the results that are returned. <em>Do not assume that the results displayed in this search portal are under a CC license</em>. You should always verify that the work is actually under a CC license by following the link. Since there is no registration to use a CC license, CC has no way to determine what has and hasn't been placed under the terms of a CC license. If you are in doubt you should contact the copyright holder directly, or try to contact the site where you found the content.</p>
				</div>
		</div>

        </div>

      </div><!--! end of .container -->
    </div><!--! end of #main -->

<?php include 'cc-wp/page-footer.php'; ?>
    </div> <!--! end of #container -->
<?php include 'cc-wp/footer-codes.php'; ?>

  <script type="text/javascript" src="//s3.amazonaws.com/downloads.mailchimp.com/js/signup-forms/popup/embed.js" data-dojo-config="usePlainJson: true, isDebug: false"></script><script type="text/javascript">require(["mojo/signup-forms/Loader"], function(L) { L.start({"baseUrl":"mc.us13.list-manage.com","uuid":"4051250396fe81f55034e2d49","lid":"5b82643372"}) })</script>

</body>
</html>
