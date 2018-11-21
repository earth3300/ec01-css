<?php
/**
 * EC01 CSS.
 *
 * Processes the CSS files in the directory in which it is placed. Concatenates
 * all the files that are not explicitly included in the exclude list. Can also
 * perform basic minification on the file. There are some security checks in place
 * and it is not recommended to place this file online, out in the wild. It is
 * intended to be used on a local machine for development purposes. This file
 * uses a namespace per PSR recommendations.
 *
 * @package Earth3300\EC01
 * @since 1.0.1
 * @author Clarence J. Bos <cbos@tnoep.ca>
 * @copyright Copyright (c) 2018, Clarence J. Bos
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html  GPL v3.0
 * @link https://github.com/earth3300/ec01-css
 *
 * @wordpress-plugin
 * Plugin Name: EC01 CSS
 * Plugin URI:  https://github.com/earth3300/ec01-css
 * Description: Concatenates the CSS files in the directory in which it is placed. Shortcode [ec01-css dir=""].
 * Version: 1.0.1
 * Author: Clarence J. Bos
 * Author URI: https://github.com/earth3300/
 * Text Domain: ec01-css
 * License:  GPL v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * File: css.php
 * Created: 2018
 * Updated: 2018-11-22
 * Time: 10:26 EST
 */

namespace Earth3300\EC01;

/**
 * Processes the CSS files in the directory in which it is placed.
 *
 * See the bottom of this file for a more complete description
 * and the switch for determining the context in which this file
 * is found.
 */
class CSS
{

  /** @var array Default options. */
  protected $opts = [
    'title' => 'EC01 CSS',
    'max' => [
              'files' => 15,
              'length' => 99000,
              ],
    'med' => [
              'files' => 5,
              'length' => 50000,
              ],
    'min' => [
              'files' => 1,
              'length' => 30000,
              ],
    'mime' => [
               'type' => 'text',
               'subtype' => 'plain',
               'ext' => '.css',
               ],
    'exclude' => [
                'style.css',
                'style.all.css',
                'style.min.css',
              ],
    'file' => [
              'write' => [
              'all' => 'style.all.css',
              'med' => 'style.css',
              'min' => 'style.min.css' ],
               ],
    'message' => [
            'na' => 'Not available.',
            'write' => [
            'denied' => 'Write permission denied.' ,
            'success' => 'Write operation succeeded.',
            'failure' => 'Write operation failed.',
            ],
          ],
    'link' => 'https://github.com/earth3300/ec01-css',
      ];

  /**
   * Gets the list of files
   *
   * Allow CSS files only.
   *
   * @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types
   *
   * @param array $args
   *
   * @return string
   */
  public function get( $args = null )
  {
    $msg = '';

    /** If no arguments are set, assume current directory */
    if ( $args = $this->setDirectorySwitch( $args ) )
    {
      $args['max'] = $this->getMaxAllowed( $args );

      $args['mime'] = $this->opts['mime'];

      $files = $this->iterateFiles( $args );

      $msg .= sprintf( '<h1>%s</h1>%s', $this->opts['title'], PHP_EOL );

      $msg .= sprintf( '<p>%s</p>%s', $files['cnt'] . ' files.', PHP_EOL );

      $msg .= sprintf( '<p>%s</p>%s', strlen ( $files['str'] ) . ' bytes.', PHP_EOL );

      if (
        '127.0.0.1' == $_SERVER['REMOTE_ADDR']
        && isset( $_GET['print'] )
        && isset( $_GET['unlock'] )
        && file_exists( __DIR__ . '/.security' )
        )
        {
          if ( $this->putContents( $files['str'] ) )
          {
            $msg .= sprintf( '<p>%s</p>%s', $this->opts['message']['write']['success'], PHP_EOL );
          }
          else
          {
            $msg .= sprintf( '<p>%s</p>%s', $this->opts['message']['write']['failure'], PHP_EOL );;
          }
        }
        else {
          $msg .= sprintf( '<p>%s</p>%s', $this->opts['message']['write']['denied'], PHP_EOL );
        }
    }
    else
    {
      $msg .= $this->opts['message']['na'];
    }
    if ( $html = $this->getPageHTML( $msg ) )
    {
      return $html;
    }
    else {
      return false;
    }
  }

  /**
   * Iterate over files.
   *
   * Allow for CSS only.
   *
   * @param array $args
   *
   * @return string
   */
  private function iterateFiles( $args )
  {
    $files['str'] = '';
    $files['files'] = [];

    /** Get the maximum number of files to process. */
    $max_files = $this->getMaxFiles();

    /** Set the count to zero and stop processing if more than allowed. */
    $cnt = 0;

    /** Get the match pattern for glob. */
    $match = $this->getMatchPattern( $args );

    foreach ( glob( $match ) as $file_and_path )
    {
      $cnt++;

      if ( '.' == $file_and_path || '..' == $file_and_path ) {
        continue;
      }

      /** Don't include the files we are excluding. in_array(needle, haystack). */
      $file = $this->getFileName( $file_and_path );

      if (
        ! empty( $file )
        && is_string( $file )
        && ! in_array( $file, $this->opts['exclude'] )
        && strpos( $file, '-dnp.') === FALSE
        )
        {
          /** Make sure we haven't gone over the number of files allowed */
          if ( $cnt < $max_files )
          {
            /** Get the contents of the file. */
            $contents = file_get_contents( $file_and_path );

            /** Make sure the file is not too long, not empty and non trivial ( > 4 bytes ). */
            $length = strlen( $contents );
            if (
              $length > 4
              && $length < $this->opts['max']['length']
              )
            {
              /** Add the contents to the total string. */
              $files['str'] .= $contents;
              $files['files'][] = $file;
            }
          }
        }
    }

    $files['cnt'] = $cnt;

    if ( $cnt > 0 )
    {
      return $files;
    }
    else
    {
      return false;
    }
  }

  /**
   * Get the maximum number of files to process.
   *
   * Checks to see if the med parameter is set. If it is, return that, else
   * use the max allowed set in opts.
   *
   * @return int
   */
   private function getMaxFiles()
   {
       if( isset( $_GET['med']))
       {
         $max_files = $this->opts['med']['files'];
       }
       else {
         $max_files = $this->opts['max']['files'];
       }
      return $max_files;
   }


  /**
   * Get the File Name from the File and Path String.
   *
   * @param string $file_and_path
   *
   * @return bool|string
   */
  private function getFileName( $str )
  {
    if ( ! empty( $str ) && strpos( $str, '/' ) !== FALSE )
    {
      $arr = explode( '/', $str );

      $last = $arr[ count( $arr ) - 1 ];

      if ( strlen( $last ) > 4 )
      {
        return $last;
      }
      else
      {
        return false;
      }
    }
  }

  /**
   * Minify
   *
   */
  private function minify( $str = '' )
  {
    /**  Ensure the string length is non-trivial. */
    if (  0 && strlen( $str ) > 1000 )
    {
      $minify = new Minifier();
      $minify->css( $str );
    }
    else
    {
      return false;
    }
  }

  /**
   * Write the files to storage.
   *
   * @param string $str
   *
   * @return bool
   */
  private function putContents( $str )
  {
    if ( ! empty( $str ) )
    {
      if ( isset( $_GET['med'] ) )
      {
        $file = $this->opts['file']['write']['med'];
      }
      elseif ( isset ( $_GET['minify'] ) )
      {
        $this->minify( $str );
      }
      {
        $file = $this->opts['file']['write']['all'];
      }

      $response = file_put_contents( $file, $str );

      return $response;
    }
  }

  /**
   * Get the SITE_PATH
   *
   * Get the SITE_PATH from the constant, from ABSPATH (if loading within WordPress
   * as a plugin), else from the $_SERVER['DOCUMENT_ROOT']
   *
   * Both of these have been tested online to have a preceding forward slash.
   * Therefore do not add one later.
   *
   * @return bool
   */
  private function getSitePath()
  {
    if ( defined( 'SITE_PATH' ) )
    {
      return SITE_PATH;
    }
    /** Available if loading within WordPress as a plugin. */
    elseif( defined( 'ABSPATH' ) )
    {
      return ABSPATH;
    }
    else
    {
      return $_SERVER['DOCUMENT_ROOT'];
    }
  }

  /**
   * Get the maximum number of files to process and the maximum length of each image allowed.
   *
   * @param array $args
   *
   * @return array
   */
  private function getMaxAllowed( $args )
  {
    if ( isset( $args['max'] ) )
    {
      $max = $args['max'];
    }
    else
    {
      $max = $this->opts['max'];
    }
    return $max;
  }

  /**
   * Build the match string.
   *
   * Allow for CSS only.
   *
   * @param array $args
   *
   * @return string|false
   */
  private function getMatchPattern( $args )
  {
    /** This will be '.css'. */
    $type = $this->opts['mime']['ext'];

    /** The base path. */
    $path = $this->getBasePath( $args );

    /** The prefix for glob. */
    $prefix = "/*";

    /** The match pattern used. */
    $match =  $path . $prefix . $type;

    /** Very basic check. Can improve, if needed. */
    if ( strlen( $match ) > 10 )
    {
      return $match;
    }
    else {
      return false;
    }
  }

  /**
   * Get the Base Path to the Item Directory.
   *
   * @param array $args
   *
   * @return string
   */
  private function getBasePath( $args )
  {
    if ( isset( $args['self'] ) )
    {
      $path = __DIR__;
    }
    elseif ( defined( 'SITE_CDN_PATH' ) )
    {
      $path = SITE_CDN_PATH;
    }
    return $path;
  }

  /**
   * Set the Directory Switch (Process Containing or Given Directory).
   *
   * If $args['self'] or $args['dir'] are not set, it assumes we are in the
   * directory for which images are to be processed. Therefore $args['self']
   * is set to true and $args['dir'] is set to null. We also have to set the
   * $args['doctype'] to true to know whether or not to wrap the output in
   * the correct doctype and the containing html and body elements.
   *
   * @param array $args
   *
   * @return array
   */
  private function setDirectorySwitch( $args )
  {
    /** If $args['dir'] is not set, set it to false. */
    $args['dir'] = isset( $args['dir'] ) ? $args['dir'] : false;

    /** if $args['dir'] == false, set $args['self'] to true. */
    if ( ! $args['dir'] )
    {
      $args['self'] = true;
      $args['doctype'] = true;
      return $args;
    }
    else
    {
      return $args;
    }
  }

  /**
   * Wrap the HTML in Page HTML `<!DOCTYPE html>`, etc.
   *
   * Use basic settings. Assume no SEO necessary. Bootstrap CSS only.
   *
   * @param string $html
   *
   * @return string|bool
   */
  private function getPageHTML( $html )
  {
    if ( ! empty( $html ) )
    {
      $str = '<!DOCTYPE html>' . PHP_EOL;
      $str .= '<html lang="en-CA">' .  PHP_EOL;
      $str .= '<head>' . PHP_EOL;
      $str .= '<meta charset="UTF-8">' . PHP_EOL;
      $str .= '<meta name="viewport" content="width=device-width, initial-scale=1"/>' . PHP_EOL;
      $str .= sprintf( '<title>%s</title>%s', $this->opts['title'], PHP_EOL );
      $str .= '<meta name="robots" content="noindex,nofollow" />' . PHP_EOL;
      $str .= '<link rel=stylesheet href="/0/theme/css/01-bootstrap.css">' . PHP_EOL;
      $str .= '</head>' . PHP_EOL;
      $str .= '<body>' . PHP_EOL;
      $str .= '<main>' . PHP_EOL;
      $str .= $html;
      $str .= '</main>' . PHP_EOL;
      $str .= '<footer>' . PHP_EOL;
      $str .= '<div class="text-center"><small>';
      $str .= 'Note: This page has been <a href="';
      $str .= $this->opts['link'];
      $str .= '">automatically generated</a>. No header, footer, menus or sidebars are available.';
      $str .= '</small></div>' . PHP_EOL;
      $str .= '</footer>' . PHP_EOL;
      $str .= '</html>' . PHP_EOL;

      return $str;
    }
    else
    {
      return false;
    }
  }
} // End Class

/**
 * Minify CSS
 *
 * Minify CSS, but keep enough line breaks intact, so it is still readable.
 */
class MinifyCSS extends CSS
{
  /**
  * CSS Minifier => http://ideone.com/Q5USEF + improvement(s)
  */
  protected function minify($input)
  {
    if(trim($input) === "")
    {
      return $input;
    }

    return preg_replace(
      array(
        // Remove comments
        '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)#s',
        // Remove unused white-spaces
        '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
        // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
        '#(?<=[:\s])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
        // Replace `:0 0 0 0` with `:0`
        '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
        // Replace `background-position:0` with `background-position:0 0`
        '#(background-position):0(?=[;\}])#si',
        // Replace `0.6` with `.6`, but only when preceded by `:`, `-`, `,` or a white-space
        '#(?<=[:\-,\s])0+\.(\d+)#s',
        // Minify string value
        '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
        '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
        // Minify HEX color code
        '#(?<=[:\-,\s]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
        // Remove empty selectors
        '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
      ),
      array(
        '$1',
        '$1$2$3$4$5$6$7',
        '$1',
        ':0',
        '$1:0 0',
        '.$1',
        '$1$3',
        '$1$2$4$5',
        '$1$2$3',
        '$1$2'
      ),
    trim($input));
  }
} // end class


/*
 * JS and CSS Minifier Class
 * version: 1.0 (2013-08-26)
 *
 * This document is licensed as free software under the terms of the
 * MIT License: http://www.opensource.org/licenses/mit-license.php
 *
 * Toni Almeida wrote this plugin, which proclaims:
 * "NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK."
 *
 * This plugin uses online webservices from javascript-minifier.com and cssminifier.com
 * This services are property of Andy Chilton, http://chilts.org/
 *
 * Copyrighted 2013 by Toni Almeida, promatik.
 *
 * Data Format:
 *
 * $css = [ "css/main.css" => "css/main.min.css" ];
 *
 * $js = [ "js/main.js" => "js/main.min.js" ];
*
 *
 * @link https://github.com/promatik/PHP-JS-CSS-Minifier/blob/master/minifier.php
 *
 * File: minifier.php
 * Created: 2018-11-21
 * Updated: 2018-11-21
 * Time: 10:06 AM
 */
class Minifier extends CSS
{
  /**
   * Minify CSS
   *
   * @param  [type] $arr [description]
   * @return [type]      [description]
   */
  protected function minifyCSS($arr)
  {
    minify($arr, 'https://cssminifier.com/raw');
  }

  /**
   * Minify JS
   *
   * @param  [type] $arr [description]
   * @return [type]      [description]
   */
  protected function minifyJS( $arr )
  {
    minify($arr, 'https://javascript-minifier.com/raw');
  }

  /**
   * Minify
   *
   * @param  [type] $arr [description]
   * @param  [type] $url [description]
   *
   * @return [type]      [description]
   */
  protected function minify( $arr, $url )
  {
    foreach ( $arr as $key => $value )
    {
      $handler = fopen($value, 'w') or die("File <a href='" . $value . "'>" . $value . "</a> error!<br />");
      fwrite($handler, getMinified($url, file_get_contents($key)));
      fclose($handler);
      echo "File <a href='" . $value . "'>" . $value . "</a> done!<br />";
    }
  }

  protected function getMinified( $url, $content )
  {
    $postdata = array('http' => array(
          'method'  => 'POST',
          'header'  => 'Content-type: application/x-www-form-urlencoded',
          'content' => http_build_query( array('input' => $content) ) ) );
    return file_get_contents($url, false, stream_context_create( $postdata ) );
  }
} // End Minifier Class

/**
 * Callback from the ec01-css shortcode.
 *
 * Performs a check, instantiates the CSS class, then starts the process.
 *
 * @param array  $args['dir']
 *
 * @return string  HTML as a list of images, wrapped in the article element.
 */
function ec01_css( $args )
{
  if ( is_array( $args ) )
  {
    $ec01_css = new CSS();
    return $ec01_css -> get( $args );
  }
  else
  {
    return '<!-- Missing the directory to process. [ec01-css dir=""] -->';
  }
}

/**
 * Check context (WordPress Plugin File or Directory Index File).
 *
 * The following checks to see whether or not this file (index.php) is being loaded
 * as part of the WordPress package, or not. If it is, we expect a WordPress
 * function to be available (in this case, `add_shortcode`). We then ensure there
 * is no direct access and add the shortcode hook, `media-index`. If we are not in
 * WordPress, then this file acts as an "indexing" type of file by listing all
 * of the allowed media types (currently jpg, png, mp3 and mp4) and making them
 * viewable to the end user by wrapping them in HTML and making use of a css
 * file that is expected to be found at `/0/media/theme/css/style.css`. This
 * idea was developed out of work to find a more robust method to develop out a
 * site, including that for a community. It makes use of the package found at:
 * {@link https://github.com/earth3300/ec01/wiki/}, with the entire codeset
 * available there through the same link.
 */
if( function_exists( 'add_shortcode' ) )
{
  // No direct access.
  defined('ABSPATH') || exit('NDA');

  //shortcode [media-index dir=""]
  add_shortcode( 'ec01-css', 'ec01_css' );
}
else
{
  /**
   * Outside of WordPress. Instantiate directly, assuming current directory.
   *
   * @return string
   */
  $ec01_css = new CSS();
  echo $ec01_css->get();
}
