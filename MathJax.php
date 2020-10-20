<?php
if(!defined('MEDIAWIKI'))
die('This is a mediawiki extensions and can\'t be run from the command line.');

$wgHooks['ParserFirstCallInit'][] = 'MathJax_Parser::RunMathJax';
$wgExtensionMessagesFiles['MathJaxMagic'] = __DIR__ . '/MathJax.i18n.magic.php';

class MathJax_Parser {
     
    static $Markers; 
    static $mark_n = 0;
    static $tempScript;

    static function RunMathJax(Parser $parser)
    {
        # TODO : this doesn't seem to work....
        # Setting the hook for <nomathjax> tag
        # c.f. https://www.mediawiki.org/wiki/Manual:Parser.php#Set
        $parser->setHook( 'nomathjax' , 'MathJax_Parser::NoMathJax' );

        global $wgHooks;

        $wgHooks['ParserBeforeStrip'][]          = 'MathJax_Parser::RemoveMathTags';
        $wgHooks['InternalParseBeforeLinks'][]   = 'MathJax_Parser::ReplaceByMarkers';
        $wgHooks['ParserAfterTidy'][]            = 'MathJax_Parser::RemoveMarkers';
        $wgHooks['BeforePageDisplay'][]          = 'MathJax_Parser::Inject_JS'; 

        return true;
    }

    static function RemoveMathTags($parser, &$text) 
    {
        $text = preg_replace('|:\s*<math>(.*?)</math>|s', '\\[$1\\]', $text);
        $text = preg_replace('|<math>(.*?)</math>|s',     '\\($1\\)', $text);

        return true;
    }

    static function Inject_JS($out)
    {
        global $wgMathJaxJS;
        global $wgMathJaxProcConf;
        global $wgMathJaxLocConf;

        $out->addScript( "<script type='text/javascript' src='" . $wgMathJaxLocConf . "'> </script>" );
        # Don't need this if we load from CDN
        #$out->addScript( "<script type='text/javascript' src='" . $wgMathJaxJS . "'> </script>" );

        return true;
    }


    static function ReplaceByMarkers(Parser $parser, &$text ) 
    {
        if (!$parser->getMagicWordFactory()->get('MAG_NOMATHJAX')->matchAndRemove($text)) {
            $text = preg_replace_callback('/(\$\$)(.*?)(\$\$)/s',                         'MathJax_Parser::Marker',$text);
            $text = preg_replace_callback('|(?<![\{\/\:\\\\])(\$)(.*?)(?<![\\\\])(\$)|s', 'MathJax_Parser::Marker', $text);
            $text = preg_replace_callback('/(\\\\\[)(.*?)(\\\\\])/s',                     'MathJax_Parser::Marker', $text);
            $text = preg_replace_callback('/(\\\\\()(.*?)(\\\\\))/s',                     'MathJax_Parser::Marker', $text);
            $text = preg_replace_callback('/(\\\begin{(?:.*?)})(.*?)(\\\end{(?:.*?)})/s', 'MathJax_Parser::Marker', $text);
        }

        return true;
    }

    static function NoMathJax( $text, array $args, Parser $parser, PPFrame $frame )
    {
        $output = $parser->recursiveTagParse($text, $frame);

        return '<span class="tex2jax_ignore">' . $output . '</span>';
    }

    static function RemoveMarkers( Parser $parser, &$text )
    {
	/** <!-- array_values seems to return a string with "..." instead of '...' so that double backslashes (\\)
	 *  becomes a single backslash (\) so that I do need the MarkerVal function since modifying a code
	 *  in math mode to match these backslashes may cause unexpected behaviors. -->
	 **/	
	#$text = preg_replace(array_keys(self::$Markers), array_values(self::$Markers), $text);

    $text = preg_replace_callback('/' . Parser::MARKER_PREFIX . 'MathJax(?:.*?)' . Parser::MARKER_SUFFIX . '/s', 'MathJax_Parser::MarkerVal', $text);

        return true;
    }

    static function MarkerVal($matches)
    {
        return self::$Markers[$matches[0]];
    }


    static function Marker($matches)
    {
        $marker = Parser::MARKER_PREFIX . 'MathJax' . ++self::$mark_n . Parser::MARKER_SUFFIX;

        $matches[2] = str_replace('<', '&#60;', $matches[2]);
        $matches[2] = str_replace('>', '&#62;', $matches[2]);
        self::$Markers[$marker] =  $matches[1] . $matches[2] . $matches[3];

        return $marker;
    }
}

